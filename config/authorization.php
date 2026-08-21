<?php

return [
    'permissions' => [
        'organization.view', 'organization.update', 'organization.create',
        'organization.suspend', 'organization.reactivate',
        'users.view', 'users.create', 'users.update', 'users.delete', 'users.invite',
        'roles.view', 'roles.create', 'roles.update', 'roles.delete',
        'permissions.view', 'permissions.assign', 'audit.view',
        'learners.view', 'learners.create', 'learners.update', 'learners.archive',
        'learners.restore', 'learners.export',
    ],
    'roles' => [
        'Organization Admin' => ['*'],
        'Manager' => [
            'organization.view', 'organization.update', 'users.view', 'users.create',
            'users.update', 'users.invite', 'roles.view', 'permissions.view',
            'learners.view', 'learners.create', 'learners.update', 'learners.archive',
            'learners.restore', 'learners.export',
        ],
        'Teacher/Trainer' => ['organization.view', 'users.view'],
        'Accountant' => [
            'organization.view', 'users.view', 'learners.view', 'learners.create',
            'learners.update', 'learners.archive', 'learners.export',
        ],
        'Staff' => [
            'organization.view', 'learners.view', 'learners.create', 'learners.update',
            'learners.archive', 'learners.export',
        ],
    ],
];
