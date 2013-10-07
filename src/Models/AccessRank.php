<?php
class AccessRank extends Enum
{
	const Anonymous = 0;
	const Registered = 1;
	const PowerUser = 2;
	const Moderator = 3;
	const Admin = 4;
}
