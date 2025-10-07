<?php

namespace App\Http\Controllers;

use App\Jobs\Job as JobsJob;
use App\Models\Bank;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Job;
use App\Views\VBank;
use App\Views\VEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getEmployees()
    {
        $employees = VEmployee::all();
        $emps = [];

        foreach ($employees as $key => $value) {
            $docs = Document::all()->where('EmployeeId', '=', $value['EmployeeId']);
            $arrDocs = [];
            foreach ($docs as $key => $val) {
                array_push($arrDocs, $val['Name']);
            }

            $positions = DB::select("call get_positions_of_one_employee(?)", [$value['EmployeeId']]);
            $arrPositions = [];
            foreach ($positions as $key => $val) {
                array_push($arrPositions, $val->Job);
            }

            $value['Doc'] = $arrDocs;
            $value['Position'] = $arrPositions;
            array_push($emps, $value);
        }

        return $this->successRes($emps);
    }

    public function getBanks()
    {
        $bank = VBank::all();
        return $this->successRes($bank);
    }

    public function getJobs()
    {
        $jobs = Job::all();
        return $this->successRes($jobs);
    }

    public function addEmployee(Request $request)
    {
        $data = $request->input('data');
        $data = json_decode($data);

        $bank = Bank::all()->where('Name', '=', $data->bankSelected)->pluck('BankId')->first();

        $newEmployee = [
            'Firstname' => mb_strtoupper($data->firstname),
            'Lastname' => mb_strtoupper($data->lastname),
            'Email' => $data->email,
            'BankId' => $bank,
            'BankAccount' => $data->account,
            'NbRSSB' => $data->rssb,
            'NbDays' => $data->nbDays
        ];

        $newEmployee = Employee::create($newEmployee);

        if (!$newEmployee) return $this->errorRes('Une erreur est survenue lors de la creation', 500);

        $documents = [];
        foreach ($data->documents as $key => $value) {
            $documents = Document::create(['EmployeeId' => $newEmployee->EmployeeId, 'Name' => $value]);
        }

        //if (!$documents) return $this->errorRes('Aucun document n\'a pu être enregistré pour cet employée', 500);

        //$positions = [];
        foreach ($data->position as $key => $value) {
            $jobId = Job::all()->where('Name', '=', $value)->pluck('JobId')->first();
            $positions = DB::insert("call add_new_position(?,?)", [$newEmployee->EmployeeId, $jobId]);
            //array_push($positions, [$newEmployee->EmployeeId, $jobId]);
            if (!$positions) return $this->errorRes('Ce poste n\'a pas pu être associé à l\'employée', 500);
        }

        //return $this->debugRes($positions);

        //$positions = DB::insert("call add_new_position(?,?)", [$newEmployee->EmployeeId, $jobId]);

        if (!$positions) return $this->errorRes('Ce poste n\'a pas pu être associé à l\'employée', 500);

        return $this->successRes("L'employée a bien été ajouté");
    }

    public function EditEmployee(Request $request)
    {
        $data = $request->input('data');
        $data = json_decode($data);

        $employee = Employee::all()->where('EmployeeId', '=', $data->EmployeeId)->first();

        $firstname = $data->Firstname;
        if (!$firstname) $firstname = $employee->Firstname;

        $lastname = $data->Lastname;
        if (!$lastname) $lastname = $employee->Lastname;

        $bankAccount = $data->bankAccount;
        if (!$bankAccount) $bankAccount = $employee->BankAccount;

        $bankSelected = $data->bankSelected;
        $bankId = Bank::all()->where('Name', '=', $bankSelected)->pluck('BankId')->first();
        if (!$bankId) $bankId = $employee->BankId;

        $email = $data->email;
        if (!$email) $email = $employee->email;

        $nbDays = $data->NbDays;
        if (!$nbDays) $nbDays = $employee->NbDays;

        $nbRssb = $data->nbRSSB;
        if (!$nbRssb) $nbRssb = $employee->NbRSSB;

        $isEmployed = filter_var($request->input('isEmployed'), FILTER_VALIDATE_BOOLEAN);

        $positions = $data->positions;
        $documents = $data->documents;

        $newEmployee = [
            'Firstname' => $firstname,
            'Lastname' => $lastname,
            'BankAccount' => $bankAccount,
            'BankId' => $bankId,
            'Email' => $email,
            'NbDays' => $nbDays,
            'NbRSSB' => $nbRssb,
            'positions' => $positions,
            'documents' => $documents,
            'isEmployed' => $isEmployed,
        ];

        $positionsToRemove = $data->positionsToRemove;

        //return $this->debugRes(['toAdd' => $data->positions, 'toReomve' => $data->positionsToRemove]);

        if ($employee->fill($newEmployee)->save()) {
            // Add the documents
            foreach ($documents as $key => $value) {
                if (!DB::select("call get_one_document_of_one_employee(?,?)", [$employee->EmployeeId, $value])) {
                    Document::create([
                        'EmployeeId' => $employee->EmployeeId,
                        'Name' => $value
                    ]);
                }
            }

            // Add the positions
            if (sizeof($positions) > 0)
                foreach ($positions as $key => $value) {
                    $p = Job::all()->where('Name', '=', $value)->pluck('JobId')->first();
                    $check = DB::select("call get_job_of_employee(?,?)", [$employee->EmployeeId, $p]);
                    if (sizeof($check) < 1) {
                        DB::insert("call set_job_to_employee(?,?)", [$employee->EmployeeId, $p]);
                    };
                }

            if (sizeof($positionsToRemove) > 0)
                foreach ($positionsToRemove as $key => $value) {
                    $p = Job::all()->where('Name', '=', $value)->pluck('JobId')->first();
                    $check = DB::select("call get_job_of_employee(?,?)", [$employee->EmployeeId, $p]);
                    if (sizeof($check) > 0) {
                        DB::delete("call unset_job_to_employee(?,?)", [$employee->EmployeeId, $p]);
                    };
                }
        };

        return $this->successRes($newEmployee);

        //return $this->debugRes($newEmployee);

    }
}
