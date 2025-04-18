<?php

use PHPUnit\Framework\TestCase;
use App\Controllers\HomeController;

class HomeControllerTest extends TestCase
{
    public function testIndex()
    {
        $controller = new HomeController();
        $this->expectOutputString("Welcome to the Attendance System!");
        $controller->index();
    }
}
