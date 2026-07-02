<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    public function index() {
        $users = $this->userService->getAll();
        return response()->json($users);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'role' => 'required|string',
            'worker_type_id' => 'nullable|exists:worker_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $this->userService->create($request->all());
        return response()->json($user);
    }

    public function update(Request $request, $id) {
        $user = $this->userService->update($request->all(), $id);
        return response()->json($user);
    }

    public function destroy($id) {
        $user = $this->userService->delete($id);
        return response()->json($user);
    }

    public function getWorkerTypes() {
        $workerTypes = $this->userService->getWorkerTypes();
        return response()->json($workerTypes);
    }
}
