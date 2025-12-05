<?php

namespace App\Traits;

use Illuminate\Contracts\Auth\Authenticatable;

trait HasPermissions
{
    /**
     * Check if authenticated user has permission
     * 
     * @param string $permission
     * @return mixed|null
     */
    protected function checkPermission($permission)
    {
        /** @var \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard $auth */
        $auth = auth();
        
        /** @var \App\Models\User|null $user */
        $user = $auth->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        if (!$user->can($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action'
            ], 403);
        }
        
        return null; // Permission granted
    }
    
    /**
     * Check if authenticated user has any of the given permissions
     * 
     * @param array $permissions
     * @return mixed|null
     */
    protected function checkAnyPermission(array $permissions)
    {
        /** @var \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard $auth */
        $auth = auth();
        
        /** @var \App\Models\User|null $user */
        $user = $auth->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return null; // Permission granted
            }
        }
        
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to perform this action'
        ], 403);
    }
    
    /**
     * Check if authenticated user has role
     * 
     * @param string $role
     * @return mixed|null
     */
    protected function checkRole($role)
    {
        /** @var \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard $auth */
        $auth = auth();
        
        /** @var \App\Models\User|null $user */
        $user = $auth->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        
        if (!$user->hasRole($role)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have the required role'
            ], 403);
        }
        
        return null; // Role granted
    }
    
    /**
     * Get authenticated user
     * 
     * @return \App\Models\User|null
     */
    protected function getAuthUser()
    {
        /** @var \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard $auth */
        $auth = auth();
        
        /** @var \App\Models\User|null $user */
        $user = $auth->user();
        
        return $user;
    }
}