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
        'group.view', 'group.create', 'group.update', 'group.archive',
        'group.restore', 'group.learners.manage',
        'schedule.view', 'schedule.create', 'schedule.update', 'schedule.cancel',
        'attendance.view', 'attendance.take', 'attendance.validate',
        'attendance.correct', 'attendance.view_reports',
    ],
    'roles' => [
        'Organization Admin' => ['*'],
        'Manager' => [
            'organization.view', 'organization.update', 'users.view', 'users.create',
            'users.update', 'users.invite', 'roles.view', 'permissions.view',
            'learners.view', 'learners.create', 'learners.update', 'learners.archive',
            'learners.restore', 'learners.export',
            'group.view', 'group.create', 'group.update', 'group.archive',
            'group.restore', 'group.learners.manage',
            'schedule.view', 'schedule.create', 'schedule.update', 'schedule.cancel',
            'attendance.view', 'attendance.take', 'attendance.validate',
            'attendance.correct', 'attendance.view_reports',
        ],
        'Teacher/Trainer' => [
            'organization.view', 'users.view', 'group.view', 'schedule.view',
            'attendance.view', 'attendance.take', 'attendance.validate',
        ],
        'Accountant' => [
            'organization.view', 'users.view', 'learners.view', 'learners.create',
            'learners.update', 'learners.archive', 'learners.export',
        ],
        'Staff' => [
            'organization.view', 'learners.view', 'learners.create', 'learners.update',
            'learners.archive', 'learners.export',
            'group.view', 'group.create', 'group.update', 'group.archive',
            'group.restore', 'group.learners.manage',
            'schedule.view', 'schedule.create', 'schedule.update', 'schedule.cancel',
            'attendance.view', 'attendance.take', 'attendance.validate',
            'attendance.correct', 'attendance.view_reports',
        ],
    ],
];
