<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('purchases:expire')->everyMinute();
