<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('libraries:check-library-expiration')->dailyAt('00:00');
Schedule::command('loans:check-overdue-loan')->dailyAt('00:00');
