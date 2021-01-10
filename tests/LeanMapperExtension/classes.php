<?php

namespace {
	if (class_exists(DibiMySqlDriver::class)) {
		class DibiFakeMySqlDriver extends DibiMySqlDriver
		{
			public function __construct()
			{
			}
		}
	}
}


namespace Dibi\Drivers {
	if (class_exists(MySqlDriver::class)) {
		class FakeMySqlDriver extends MySqlDriver
		{
			public function __construct()
			{
			}
		}
	}
}
