'use strict';
const http = require('node:http');
const crypto = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');
const { DatabaseSync } = require('node:sqlite');

const DB_PATH = process.env.DATABASE_PATH || path.join(__dirname, 'data', 'vibsies.sqlite');
fs.mkdirSync(path.dirname(DB_PATH), { recursive: true });
const db = new DatabaseSync(DB_PATH);
db.exec(`
PRAGMA foreign_keys=ON;
CREATE TABLE IF NOT EXISTS users (
 id TEXT PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE,
 password TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'customer'
);
CREATE TABLE IF NOT EXISTS creators (
 id TEXT PRIMARY KEY, user_id TEXT NOT NULL UNIQUE REFERENCES users(id),
 name TEXT NOT NULL, category TEXT NOT NULL, bio TEXT NOT NULL DEFAULT ''
);
CREATE TABLE IF NOT EXISTS packages (
 id TEXT PRIMARY KEY, creator_id TEXT NOT NULL REFERENCES creators(id),
 title TEXT NOT NULL, price INTEGER NOT NULL CHECK(price>0),
 days INTEGER NOT NULL DEFAULT 7
);
CREATE TABLE IF NOT EXISTS bookings (
 id TEXT PRIMARY KEY, package_id TEXT NOT NULL REFERENCES packages(id),
 customer_id TEXT NOT NULL REFERENCES users(id),
 description TEXT NOT NULL, base_price INTEGER NOT NULL,
 fee INTEGER NOT NULL, status TEXT NOT NULL DEFAULT 'requested',
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS sessions (
 token_hash TEXT PRIMARY KEY, user_id TEXT NOT NULL REFERENCES users(id),
 expires INTEGER NOT NULL
);`);
const uid = () => crypto.randomUUID();
const esc = v => String(v ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const hashPassword = p => crypto.scryptSync(p, 'vibsies-v1', 64).toString('hex');
const pwdMatch=(p,s)=> crypto.timingSafeEqual(Buffer.from(hashPassword(p),'hex'),Buffer.from(s,'hex'));
const form = async req => {
 let value=''; for await (const b of req) {value+=b;if(value.length>16000) throw new Error('Too large');}
 return Object.fromEntries(new URLSearchParams(value));
};
const redirect=(res,url)=>{res.writeHead(303,{Location:url});res.end();};
const userFor=req=>{
 const token=/vibsies_session=([^;]+)/.exec(req.headers.cookie||'')?.[1];
 if(!token) return null;
 return db.prepare('SELECT u.* FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token_hash=? AND s.expires>?')
  .get(crypto.createHash('sha256').update(token).digest('hex'),Date.now())||null;
};
const nav=u=>`<header><a class="brand" href="/">vibsies<span>●</span></a><nav><a href="/creators">Explore creators</a>${u?'<a href="/dashboard">Dashboard</a><form method="post" action="/logout"><button>Log out</button></form>':'<a href="/login">Log in</a><a class="pill" href="/register">Join Vibsies</a>'}</nav></header>`;
const page=(title,body,u)=>`<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${esc(title)} | Vibsies</title><style>
:root{font:16px system-ui,sans-serif;color:#1a1930;background:#faf8ff}*{box-sizing:border-box}body{margin:0}a{color:inherit;text-decoration:none}header{display:flex;justify-content:space-between;align-items:center;padding:18px max(4vw,20px);background:white;box-shadow:0 2px 18px #24203a0c;gap:16px}.brand{font-size:29px;font-weight:900;letter-spacing:-1px}.brand span{color:#ef668e}nav{display:flex;align-items:center;gap:20px;flex-wrap:wrap}button,.pill{background:#6f45d4;color:white;border:0;padding:12px 19px;border-radius:12px;cursor:pointer;font-weight:700}nav button{padding:7px 12px}main{max-width:1110px;margin:auto;padding:48px 20px}.hero{background:linear-gradient(135deg,#241743,#693ac6);color:white;border-radius:28px;padding:65px 45px}.hero h1{font-size:clamp(32px,5vw,62px);max-width:720px;line-height:1.1;margin:0 0 20px}.hero p{font-size:19px;max-width:650px;opacity:.88}.hero .pill{display:inline-block;background:#ffbf78;color:#231442;margin-top:12px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(245px,1fr));gap:18px;margin:25px 0}.card,.panel{background:white;border:1px solid #e7e0f2;border-radius:18px;padding:24px;box-shadow:0 5px 22px #4c34660b}.card .emoji{font-size:38px}.muted{color:#686277}.tag{color:#7944c9;font-weight:700;font-size:13px}.price{font-weight:800;font-size:22px}form.stack{display:grid;gap:12px;max-width:560px}input,textarea,select{display:block;width:100%;padding:13px;border:1px solid #d5cce6;border-radius:10px;font:inherit}label{font-weight:600}small{color:#696176}.error{color:#b52d59}h2{margin-top:30px}footer{text-align:center;padding:50px;color:#827d8d}@media(max-width:650px){header{align-items:flex-start;flex-direction:column}.hero{padding:36px 25px}}
</style><body>${nav(u)}<main>${body}</main><footer>Vibsies — A Barmageyat product · Development prototype</footer></body></html>`;
const requireUser=(res,u)=>{if(!u){redirect(res,'/login');return false;}return true;};
async function handler(req,res){
 try {
 const url=new URL(req.url,'http://localhost');
 const u=userFor(req);
 if(req.method==='GET' && url.pathname==='/health'){res.writeHead(200,{'Content-Type':'application/json'});return res.end(JSON.stringify({ok:true}));}
 if(req.method==='GET' && url.pathname==='/'){
  return res.end(page('Home',`<section class="hero"><h1>Big ideas. Real creators. Great videos.</h1><p>Discover creators for reviews, personalized videos, product demos and sponsored content. Book confidently, collaborate simply.</p><a class="pill" href="/creators">Find your creator →</a></section><h2>How it works</h2><div class="grid">${['Discover and compare','Request a video','Create together'].map((v,i)=>`<div class="card"><div class="emoji">${['🔎','🎬','✨'][i]}</div><h3>${v}</h3><p class="muted">${['Explore creator profiles and offers.','Send a brief and agree the scope.','Track requests through your dashboard.'][i]}</p></div>`).join('')}</div><p><a href="/register">Want to become a creator? Join Vibsies →</a></p>`,u));
 }
 if(req.method==='GET'&&url.pathname==='/register') return res.end(page('Register',`<h1>Join Vibsies</h1><form class="panel stack" method="post" action="/register"><label>Name<input name="name" required maxlength="100"></label><label>Email<input type="email" name="email" required></label><label>Password (min 10 characters)<input name="password" type="password" minlength="10" required></label><button>Create account</button></form>`,u));
 if(req.method==='POST'&&url.pathname==='/register'){
  const d=await form(req);if(!d.name?.trim()||!d.email?.includes('@')||!d.password||d.password.length<10)throw new Error('Invalid registration information');
  const id=uid();db.prepare('INSERT INTO users(id,name,email,password) VALUES(?,?,?,?)').run(id,d.name.trim(),d.email.trim().toLowerCase(),hashPassword(d.password));
  const token=crypto.randomBytes(32).toString('hex');db.prepare('INSERT INTO sessions VALUES(?,?,?)').run(crypto.createHash('sha256').update(token).digest('hex'),id,Date.now()+86400000);
  res.setHeader('Set-Cookie',`vibsies_session=${token}; HttpOnly; SameSite=Lax; Path=/; Max-Age=86400`);
  return redirect(res,'/dashboard');
 }
 if(req.method==='GET'&&url.pathname==='/login') return res.end(page('Log in',`<h1>Welcome back</h1><form class="panel stack" method="post" action="/login"><label>Email<input type="email" name="email" required></label><label>Password<input type="password" name="password" required></label><button>Log in</button></form>`,u));
 if(req.method==='POST'&&url.pathname==='/login'){
  const d=await form(req);const account=db.prepare('SELECT * FROM users WHERE email=?').get((d.email||'').trim().toLowerCase());
  if(!account||!pwdMatch(d.password||'',account.password))throw new Error('Incorrect login');
  const token=crypto.randomBytes(32).toString('hex');db.prepare('INSERT INTO sessions VALUES(?,?,?)').run(crypto.createHash('sha256').update(token).digest('hex'),account.id,Date.now()+86400000);
  res.setHeader('Set-Cookie',`vibsies_session=${token}; HttpOnly; SameSite=Lax; Path=/; Max-Age=86400`);
  return redirect(res,'/dashboard');
 }
 if(req.method==='POST'&&url.pathname==='/logout'){
  if(u){const token=/vibsies_session=([^;]+)/.exec(req.headers.cookie||'')?.[1];if(token)db.prepare('DELETE FROM sessions WHERE token_hash=?').run(crypto.createHash('sha256').update(token).digest('hex'));}
  res.setHeader('Set-Cookie','vibsies_session=; HttpOnly; SameSite=Lax; Path=/; Max-Age=0');return redirect(res,'/');
 }
 if(req.method==='GET'&&url.pathname==='/creators'){
  const q=(url.searchParams.get('q')||'').slice(0,80);
  const rows=db.prepare(`SELECT c.*,p.id package_id,p.title,p.price,p.days FROM creators c JOIN packages p ON p.creator_id=c.id WHERE c.name LIKE ? OR c.category LIKE ? ORDER BY c.name LIMIT 100`).all('%'+q+'%','%'+q+'%');
  return res.end(page('Explore creators',`<h1>Explore creators</h1><form method="get" action="/creators"><input name="q" value="${esc(q)}" placeholder="Search by creator or specialty"></form><div class="grid">${rows.map(r=>`<article class="card"><div class="emoji">🎥</div><div class="tag">${esc(r.category)}</div><h3>${esc(r.name)}</h3><p>${esc(r.bio)}</p><b>${esc(r.title)}</b><p class="price">EGP ${(r.price/100).toFixed(2)}</p><a class="pill" href="/book?package=${esc(r.package_id)}">Request video</a></article>`).join('')||'<p>No creator packages yet. Become the first!</p>'}</div>`,u));
 }
 if(req.method==='GET'&&url.pathname==='/creator/setup'){
  if(!requireUser(res,u))return;
  return res.end(page('Creator setup',`<h1>Become a creator</h1><form class="panel stack" method="post" action="/creator/setup"><label>Creator display name<input name="name" required></label><label>Specialty<input name="category" placeholder="UGC, lifestyle, restaurant reviews" required></label><label>Short bio<textarea name="bio"></textarea></label><label>Package title<input name="title" placeholder="30-second product demo" required></label><label>Package price (EGP)<input type="number" min="1" step="1" name="price" required></label><label>Turnaround (days)<input type="number" min="1" max="60" name="days" value="7" required></label><button>Publish my package</button></form>`,u));
 }
 if(req.method==='POST'&&url.pathname==='/creator/setup'){
  if(!requireUser(res,u))return;const d=await form(req);
  const price=Number(d.price),days=Number(d.days);
  if(!d.name?.trim()||!d.category?.trim()||!d.title?.trim()||!Number.isSafeInteger(price)||price<1||!Number.isSafeInteger(days)||days<1||days>60)throw new Error('Invalid creator package');
  let creator=db.prepare('SELECT * FROM creators WHERE user_id=?').get(u.id);
  if(!creator){const id=uid();db.prepare('INSERT INTO creators(id,user_id,name,category,bio) VALUES(?,?,?,?,?)').run(id,u.id,d.name.slice(0,100),d.category.slice(0,100),(d.bio||'').slice(0,1000));creator={id};}
  db.prepare('INSERT INTO packages(id,creator_id,title,price,days) VALUES(?,?,?,?,?)').run(uid(),creator.id,d.title.slice(0,120),price*100,days);
  return redirect(res,'/dashboard');
 }
 if(req.method==='GET'&&url.pathname==='/book'){
  if(!requireUser(res,u))return;
  const p=db.prepare('SELECT p.*,c.name creator_name,c.user_id creator_user FROM packages p JOIN creators c ON c.id=p.creator_id WHERE p.id=?').get(url.searchParams.get('package'));
  if(!p)return res.end(page('Not found','<h1>Package not found</h1>',u));
  return res.end(page('Book video',`<h1>Request a video</h1><div class="panel"><h3>${esc(p.title)} by ${esc(p.creator_name)}</h3><p>Creator price: EGP ${(p.price/100).toFixed(2)}</p><p>Platform service fee (15%): EGP ${Math.round(p.price*.15)/100}</p><h3>Estimated total: EGP ${(p.price+Math.round(p.price*.15))/100}</h3><small>Payments and enforceable contracts are not enabled in this prototype.</small><form method="post" action="/book" class="stack"><input type="hidden" name="package" value="${esc(p.id)}"><label>Your video brief<textarea name="description" required maxlength="1500"></textarea></label><button>Send booking request</button></form></div>`,u));
 }
 if(req.method==='POST'&&url.pathname==='/book'){
  if(!requireUser(res,u))return;const d=await form(req);const p=db.prepare('SELECT p.*,c.user_id creator_user FROM packages p JOIN creators c ON c.id=p.creator_id WHERE p.id=?').get(d.package);
  if(!p||p.creator_user===u.id||!d.description?.trim())throw new Error('Invalid booking');
  db.prepare('INSERT INTO bookings(id,package_id,customer_id,description,base_price,fee) VALUES(?,?,?,?,?,?)').run(uid(),p.id,u.id,d.description.slice(0,1500),p.price,Math.round(p.price*.15));
  return redirect(res,'/dashboard');
 }
 if(req.method==='GET'&&url.pathname==='/dashboard'){
  if(!requireUser(res,u))return;
  const own=db.prepare('SELECT c.id FROM creators c WHERE c.user_id=?').get(u.id);
  const customer=db.prepare('SELECT b.*,p.title,c.name creator_name FROM bookings b JOIN packages p ON p.id=b.package_id JOIN creators c ON c.id=p.creator_id WHERE b.customer_id=? ORDER BY b.created_at DESC').all(u.id);
  const incoming=own?db.prepare('SELECT b.*,p.title,us.name customer_name FROM bookings b JOIN packages p ON p.id=b.package_id JOIN users us ON us.id=b.customer_id WHERE p.creator_id=? ORDER BY b.created_at DESC').all(own.id):[];
  const cards=(items,creator)=>items.map(b=>`<div class="card"><div class="tag">${esc(b.status)}</div><h3>${esc(b.title)}</h3><p>${esc(creator?b.customer_name:b.creator_name)}</p><p>${esc(b.description)}</p><p>EGP ${((b.base_price+b.fee)/100).toFixed(2)} including platform fee</p>${creator&&b.status==='requested'?`<form method="post" action="/booking/status"><input type="hidden" name="id" value="${esc(b.id)}"><button name="status" value="accepted">Accept</button> <button name="status" value="declined">Decline</button></form>`:''}</div>`).join('')||'<p class="muted">No bookings yet.</p>';
  return res.end(page('Dashboard',`<h1>Hello, ${esc(u.name)}</h1><p><a class="pill" href="/creator/setup">Create a creator package</a> <a href="/creators">Explore creators →</a></p><h2>My requests</h2><div class="grid">${cards(customer,false)}</div><h2>Creator bookings</h2><div class="grid">${cards(incoming,true)}</div>`,u));
 }
 if(req.method==='POST'&&url.pathname==='/booking/status'){
  if(!requireUser(res,u))return;const d=await form(req);
  if(!['accepted','declined'].includes(d.status))throw new Error('Invalid booking status');
  db.prepare(`UPDATE bookings SET status=? WHERE id=? AND status='requested' AND package_id IN (SELECT p.id FROM packages p JOIN creators c ON c.id=p.creator_id WHERE c.user_id=?)`).run(d.status,d.id,u.id);
  return redirect(res,'/dashboard');
 }
 res.writeHead(404);return res.end('Not found');
 } catch(e){res.statusCode=400;return res.end(page('Error',`<h1>Unable to complete request</h1><p class="error">${esc(e.message.includes('UNIQUE')?'That email is already registered.':e.message)}</p><a href="/">Home</a>`,null));}
}
if(require.main===module){http.createServer(handler).listen(Number(process.env.PORT||3000),()=>console.log('Vibsies running on port '+(process.env.PORT||3000)));}
module.exports={handler,hashPassword,pwdMatch,db};