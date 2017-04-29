<?php

namespace {
	if (class_exists('DibiMySqlDriver')) {
		class DibiFakeMySqlDriver extends DibiMySqlDriver
		{
			public function __construct()
			{
			}
		}
	}
}


namespace Dibi\Drivers {
	if (class_exists('Dibi\Drivers\MySqlDriver')) {
		class FakeMySqlDriver extends MySqlDriver
		{
			public function __construct()
			{
			}
		}
	}
}
