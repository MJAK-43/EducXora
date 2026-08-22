<?php

declare(strict_types=1);

namespace App\Domain\Pedagogy\Enums;

enum LevelChangeSource: string
{
    case PlacementTest = 'placement_test';
    case TeacherEvaluation = 'teacher_evaluation';
    case DirectorOverride = 'director_override';
}
