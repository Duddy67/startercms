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
        'alert_user_dependencies' => 'You cannot delete this user: :name as he/she owns :number :dependencies. Please modify these dependencies accordingly then try again.',
        'unknown_user' => 'Unknown user.',
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
        'missing_alert' => '(missing !)',
    ],
    'groups' => [
        'create_success' => 'Group successfully created.',
        'update_success' => 'Group successfully updated.',
        'delete_success' => 'The group ":name" has been successfully deleted.',
        'delete_list_success' => ':number groups have been successfully deleted.',
        'edit_not_auth' => 'You are not authorised to edit groups.',
        'delete_not_auth' => 'You are not authorised to delete groups.',
    ],
    'posts' => [
        'create_success' => 'Post successfully created.',
        'update_success' => 'Post successfully updated.',
        'delete_success' => 'The post ":name" has been successfully deleted.',
        'delete_list_success' => ':number posts have been successfully deleted.',
        'edit_not_auth' => 'You are not authorised to edit posts.',
        'delete_not_auth' => 'You are not authorised to delete posts.',
        'publish_list_success' => ':number posts have been successfully published.',
        'unpublish_list_success' => ':number posts have been successfully unpublished.',
    ],
    'categories' => [
        'create_success' => 'category successfully created.',
        'update_success' => 'category successfully updated.',
        'change_status_list_success' => 'category statuses successfully changed.',
        'no_subcategories' => 'No sub-categories',
    ],
    'emails' => [
        'create_success' => 'Email successfully created.',
        'update_success' => 'Email successfully updated.',
        'delete_success' => 'The email ":name" has been successfully deleted.',
        'delete_list_success' => ':number emails have been successfully deleted.',
        'edit_not_auth' => 'You are not authorised to edit emails.',
        'delete_not_auth' => 'You are not authorised to delete emails.',
    ],
    'documents' => [
        'create_success' => 'Document successfully created.',
        'delete_success' => 'The document ":name" has been successfully deleted.',
    ],
    'menus' => [
        'menu_not_found' => 'The menu with the code: :code cannot be found.',
    ],
    'general' => [
        'update_success' => 'Parameters successfully saved.',
    ],
    'generic' => [
        'ressource_not_found' => 'Ressource not found.',
        'access_not_auth' => 'You are not authorised to access this resource.',
        'edit_not_auth' => 'You are not authorised to edit this resource.',
        'create_not_auth' => 'You are not authorised to create a resource.',
        'delete_not_auth' => 'You are not authorised to delete this resource.',
        'change_status_not_auth' => 'You are not authorised to change the status of this resource.',
        'change_order_not_auth' => 'You are not authorised to change the order of this resource.',
        'user_id_does_not_match' => 'The id of the user supposed to edit this item doesn\'t match with your id. Or may be you\'ve been checked in by an administrator.',
        'owner_not_valid' => 'The owner of the item is not valid.',
        'no_item_selected' => 'No item selected.',
        'mass_update_success' => ':number items successfully updated.',
        'mass_delete_success' => ':number item(s) have been successfully deleted.',
        'check_in_success' => ':number items successfully checked-in.',
        'check_in_not_auth' => 'You are not authorised to check-in some of the selected items.',
        'mass_update_not_auth' => 'You are not authorised to update some of the selected items.',
        'mass_delete_not_auth' => 'You are not authorised to delete some of the selected items.',
        'mass_publish_not_auth' => 'You are not authorised to publish some of the selected items.',
        'mass_unpublish_not_auth' => 'You are not authorised to unpublish some of the selected items.',
        'must_not_be_descendant' => 'Node must not be a descendant.',
        'item_is_private' => ':name item is private.',
    ]
];
