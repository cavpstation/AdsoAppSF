<?php

namespace Illuminate\Tests\Integration\Database\EloquentBelongsToManyEagerLoadPivotRelationsTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentBelongsToManyEagerLoadPivotRelationsTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('employees', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('deductions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('employees_deductions', function (Blueprint $table) {
            $table->foreignId('employee_id');
            $table->foreignId('deduction_id');
            $table->foreignId('payroll_period_id');
            $table->foreignId('user_id')->nullable();
            $table->decimal('amount', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('payroll_periods_users', function (Blueprint $table) {
            $table->foreignId('payroll_period_id');
            $table->foreignId('user_id');
            $table->foreignId('note_id');
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Model::preventLazyLoading(false);
    }

    public function testCanEagerLoadPivotRelations()
    {
        $employee = Employee::create(['name' => Str::random()]);
        $deduction = Deduction::create(['name' => Str::random()]);
        $payrollPeriod = PayrollPeriod::create(['name' => Str::random()]);
        $employee->deductions()->attach($deduction->id, [
            'payroll_period_id' => $payrollPeriod->id,
            'amount' => 100,
        ]);

        $employee = Employee::with('deductions.pivot.payrollPeriod')->get()->first();

        $pivot = $employee->deductions->first()->pivot;

        $this->assertTrue($pivot->relationLoaded('payrollPeriod'));

        $this->assertInstanceOf(PayrollPeriod::class, $pivot->payrollPeriod);
    }

    public function testCanEagerLoadManyPivotRelations()
    {
        $employee = Employee::create(['name' => Str::random()]);
        $deduction = Deduction::create(['name' => Str::random()]);
        $payrollPeriod = PayrollPeriod::create(['name' => Str::random()]);
        $user = User::create(['name' => Str::random()]);
        $employee->deductions()->attach($deduction->id, [
            'payroll_period_id' => $payrollPeriod->id,
            'user_id' => $user->id,
            'amount' => 100,
        ]);

        $employee = Employee::with([
            'deductions.pivot.payrollPeriod',
            'deductions.pivot.user',
        ])->get()->first();

        $pivot = $employee->deductions->first()->pivot;

        $this->assertTrue($pivot->relationLoaded('payrollPeriod'));
        $this->assertInstanceOf(PayrollPeriod::class, $pivot->payrollPeriod);

        $this->assertTrue($pivot->relationLoaded('user'));
        $this->assertInstanceOf(User::class, $pivot->user);
    }


    public function testCanEagerLoadNestedPivotRelations()
    {
        $employee = Employee::create(['name' => Str::random()]);
        $deduction = Deduction::create(['name' => Str::random()]);
        $payrollPeriod = PayrollPeriod::create(['name' => Str::random()]);

        $employee->deductions()->attach($deduction->id, [
            'payroll_period_id' => $payrollPeriod->id,
            'amount' => 100,
        ]);

        $user = User::create(['name' => Str::random()]);
        $note = Note::create(['name' => Str::random()]);
        $payrollPeriod->users()->attach($user->id, [
            'note_id' => $note->id,
        ]);

        $employee = Employee::with('deductions.pivot.payrollPeriod.users.pivot.note')->get()->first();

        $pivot = $employee->deductions->first()->pivot;

        $this->assertTrue($pivot->relationLoaded('payrollPeriod'));
        $this->assertTrue($pivot->payrollPeriod->relationLoaded('users'));

        $payrollPeriodUserPivot = $pivot->payrollPeriod->users->first()->pivot;

        $this->assertTrue($payrollPeriodUserPivot->relationLoaded('note'));
    }

    public function testAccessOnPivotRelationsWillThrowLazyLoadingViolationExceptionIfNotEagerLoaded()
    {
        $this->expectException(LazyLoadingViolationException::class);
        $this->expectExceptionMessage('Attempted to lazy load');

        $employee = Employee::create(['name' => Str::random()]);
        $deduction = Deduction::create(['name' => Str::random()]);
        $payrollPeriod = PayrollPeriod::create(['name' => Str::random()]);
        $employee->deductions()->attach($deduction->id, [
            'payroll_period_id' => $payrollPeriod->id,
            'amount' => 100,
        ]);

        Model::preventLazyLoading();

        $employee->deductions()->get()->first()->pivot->payrollPeriod;
    }

    public function testAccessOnPivotRelationsWillBeOkayIfEagerLoaded()
    {
        $employee = Employee::create(['name' => Str::random()]);
        $deduction = Deduction::create(['name' => Str::random()]);
        $payrollPeriod = PayrollPeriod::create(['name' => Str::random()]);
        $employee->deductions()->attach($deduction->id, [
            'payroll_period_id' => $payrollPeriod->id,
            'amount' => 100,
        ]);

        Model::preventLazyLoading();

        $employee->deductions()->with('pivot.payrollPeriod')->get()->first()->pivot->payrollPeriod;
    }
}

class Employee extends Model
{
    public $table = 'employees';
    public $timestamps = true;
    protected $guarded = [];

    public function deductions()
    {
        return $this->belongsToMany(Deduction::class, 'employees_deductions')
            ->withTimestamps()
            ->withPivot([
                'payroll_period_id',
                'user_id',
                'amount',
            ])
            ->using(EmployeeDeduction::class);
    }
}

class Deduction extends Model
{
    public $table = 'deductions';
    public $timestamps = true;
    protected $guarded = [];
}

class PayrollPeriod extends Model
{
    public $table = 'payroll_periods';
    public $timestamps = true;
    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(User::class, 'payroll_periods_users')
            ->withPivot('note_id')
            ->using(PayrollPeriodUser::class)
            ->withTimestamps();
    }
}

class EmployeeDeduction extends Pivot
{
    protected $table = 'employees_deductions';

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class User extends Model
{
    public $table = 'users';
    public $timestamps = true;
    protected $guarded = [];
}

class Note extends Model
{
    public $table = 'notes';
    public $timestamps = true;
    protected $guarded = [];
}

class PayrollPeriodUser extends Pivot
{
    protected $table = 'payroll_periods_users';

    public function note()
    {
        return $this->belongsTo(Note::class);
    }
}
