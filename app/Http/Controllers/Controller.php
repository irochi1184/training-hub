<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Policyを使った認可を全コントローラで利用できるようにする
    use AuthorizesRequests;
}
