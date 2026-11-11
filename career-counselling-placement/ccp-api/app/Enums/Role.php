<?php

namespace App\Enums;

enum Role: string
{
    case Student = 'student';
    case Advisor = 'advisor';
    case Admin = 'admin';
    case Recruiter = 'recruiter';
}
