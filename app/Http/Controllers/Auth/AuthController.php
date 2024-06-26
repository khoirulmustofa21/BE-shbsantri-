<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Response\CustomsResponse;
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 400,
                'message' => 'Validation Error.',
                'errors' => $validator->errors()
            ], 400);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('SHBSANTRI')->plainTextToken;
        $success['name'] =  $user->name;

        $data = [
            "id" => $user->id,
            "name" => $success['name'],
            "token" => $success['token'],
            "email" => $user->email,
            "avatar" => $user->avatar,
            "created_at" => $user->created_at,
            "updated_at" => $user->updated_at
        ];

        return CustomsResponse::success(
          $data,
            'User register successfully.',
            201
        );
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if($validator->fails()){
            return CustomsResponse::error(
                $validator->errors(),
                'Validation Error.',
                400
            );
        }

        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){
            $user = Auth::user();
            $success['token'] =  $user->createToken('SHBSANTRI')->plainTextToken;
            $success['name'] =  $user->name;

            $data = [
                "name" => $success['name'],
                "token" => $success['token'],
                "email" => $user->email,
                "avatar" => $user->avatar
            ];
            return CustomsResponse::success(
                $data,
                'User login successfully.',
                200
            );
        }
        else{
            return response()->json([
                'status' => 400,
                'message' => 'Unauthorised.',
            ]);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'avatar' => 'nullable|string',
            'c_password' => 'nullable|string|same:password',
            'is_subscribe' => 'nullable|boolean',
        ]);

        // Check if validation fails
        if ($validator->fails()) {

            return CustomsResponse::error(
                null,
                'Validation Error.',
                400
            );
        }

        // Update user data
        $user->name = $request->name;
        $user->email = $request->email;
        // Update other user attributes as needed
        $user->save();



        return CustomsResponse::success(
            $user,
            'User profile updated successfully.',
            200
        );
    }
    public function logout(): JsonResponse
    {
        $user = Auth::user();
        $user->tokens()->delete();



        return response()->json([
            'status' => 200,
            'message' => 'User logout successfully.',
        ]);
    }

    /**
     * Remove the specified user from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id) : JsonResponse
    {
        try {
            // Cari pengguna berdasarkan ID
            $user = User::findOrFail($id);

            // Hapus pengguna
            $user->delete();

            return CustomsResponse::success( 'User deleted successfully.', 200);
        } catch (\Exception $e) {
            return CustomsResponse::error(
                $e->getMessage(),
                'Failed to delete user.',
                500,
            );
        }
    }
}
