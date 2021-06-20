<?php

return [

    'users' => [
        'update_success' => 'User successfully updated.',
        'create_success' => 'User successfully created.',
        'delete_success' => 'The user ":name" has been successfully deleted.',
        'delete_list_success' => ':number users have been successfully deleted.',
        'edit_not_auth' => 'You are not authorised to edit users.',
        'delete_not_auth' => 'You are not authorised to delete users.',
        'edit_user_not_auth' => 'You are not authorised to edit this user.',
        'update_user_not_auth' => 'You are not authorised to update this user.',
        'delete_user_not_auth' => 'You are not authorised to delete this user.',
        'delete_list_not_auth' => 'You are not authorised to delete this user: :name',
    ],
    'roles' => [
        'create_success' => 'Role successfully created.',
        'update_success' => 'Role successfully updated.',
        'delete_success' => 'The role ":name" has been successfully deleted.',
        'delete_list_success' => ':number roles have been successfully deleted.',
        'edit_not_auth' => 'You are not authorised to edit roles.',
        'delete_not_auth' => 'You are not authorised to delete roles.',
        'cannot_update_default_roles' => 'You cannot modify the default roles.',
        'cannot_delete_default_roles' => 'You cannot delete the default roles.',
        'permission_not_auth' => 'One or more selected permissions are not authorised.',
        'users_assigned_to_roles' => 'One or more users are assigned to this role: :name',
        'cannot_delete_roles' => 'The following roles cannot be deleted: :roles',
    ],
    'permissions' => [
        'role_does_not_exist' => 'This role :name does not exist.',
        'invalid_permission_names' => 'The permission names: :names are invalid.',
        'build_success' => ':number permissions have been successfully built.',
        'rebuild_success' => ':number permissions have been successfully rebuilt.',
        'no_new_permissions' => 'No new permissions have been built.',
    ],
    'groups' => [
        'create_success' => 'Group successfully created.',
        'update_success' => 'Group successfully updated.',
        'delete_success' => 'The group ":name" has been successfully deleted.',
        'delete_list_success' => ':number groups have been successfully deleted.',
        'edit_not_auth' => 'You are not authorised to edit groups.',
        'delete_not_auth' => 'You are not authorised to delete groups.',
    ],
    'general' => [
        'update_success' => 'Parameters successfully saved.',
    ],
    'generic' => [
        'access_not_auth' => 'You are not authorised to access this resource.',
    ]
];
