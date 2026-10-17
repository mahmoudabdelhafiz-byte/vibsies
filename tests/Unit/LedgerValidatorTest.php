<?php
namespace Tests\Unit;
use App\Services\Accounting\LedgerValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
class LedgerValidatorTest extends TestCase {
 public function test_balanced_entries_are_valid():void {(new LedgerValidator)->validate([['debit_minor'=>2300,'credit_minor'=>0],['debit_minor'=>0,'credit_minor'=>2300]]);$this->assertTrue(true);}
 public function test_unbalanced_entries_are_rejected():void {$this->expectException(InvalidArgumentException::class);(new LedgerValidator)->validate([['debit_minor'=>2300,'credit_minor'=>0],['debit_minor'=>0,'credit_minor'=>2000]]);}
}