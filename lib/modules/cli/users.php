#!/usr/bin/php
<?php
# Copyright (c) 2017, CESNET. All rights reserved.
#
# Redistribution and use in source and binary forms, with or
# without modification, are permitted provided that the following
# conditions are met:
#
#   o Redistributions of source code must retain the above
#     copyright notice, this list of conditions and the following
#     disclaimer.
#   o Redistributions in binary form must reproduce the above
#     copyright notice, this list of conditions and the following
#     disclaimer in the documentation and/or other materials
#     provided with the distribution.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
# CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
# INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
# MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
# DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS
# BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
# EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
# TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
# DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
# ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
# OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
# OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
# POSSIBILITY OF SUCH DAMAGE.

require(realpath(dirname(__FILE__)) . '/../../common/Loader.php');

$shortopts = "c:h";

$longopts = array(
      "uid:",
      "name:",
      "email:",
      "admin",
      "help",
);

function usage()
{
    die("Usage: users [-h|--help] (-c store (--uid=<uid>) [--name=<name>] [--email=<email>] [--admin] | delete (--uid=<uid>) | list)\n");
}

$opt = getopt($shortopts, $longopts);

if (isset($opt["h"]) || isset($opt["help"]) || !isset($opt["c"])) {
    usage();
}

switch ($opt["c"]) {

  # store user
  case "store":
    $user = new User();
    if (isset($opt["uid"])) {
        $user->setUid($opt["uid"]);
    } else {
        die("required option uid is missing\n");
    }
    if (isset($opt["name"])) {
        $user->setName($opt["name"]);
    }
    if (isset($opt["email"])) {
        $user->setEmail($opt["email"]);
    }
    if (isset($opt["admin"])) {
        $user->setAdmin(true);
    }
    if ($pakiti->getManager("UsersManager")->storeUser($user)) {
        die("user was created\n");
    } else {
        die("user was updated\n");
    }
    break;

  # delete user
  case "delete":
    if (isset($opt["uid"])) {
        $id = $pakiti->getManager("UsersManager")->getUserIdByUid($opt["uid"]);
    } else {
        die("required option uid is missing\n");
    }
    if ($pakiti->getManager("UsersManager")->deleteUser($id)) {
        die("user was deleted\n");
    } else {
        die("user wasn't deleted\n");
    }
    break;

  # list users
  case "list":
    $users = $pakiti->getManager("UsersManager")->getUsers();
    print "uid\tname\temail\tadmin\n";
    foreach ($users as $user) {
        print $user->getUid()."\t".$user->getName()."\t".$user->getEmail()."\t".$user->isAdmin()."\n";
    }
    break;

    # import users
    case "import":
        $data = json_decode(file_get_contents("php://stdin"), true);
        if ($data == null || !is_array($data)) {
            die("bad format");
        }
        $ids = array();
        $manager = $pakiti->getManager("UsersManager");
        foreach ($data as $obj) {
            if (!array_key_exists(Config::$USERS_UID, $obj)) {
                print "required variable uid is missing, user is omitted\n";
                continue;
            }
            $uids = array($obj[Config::$USERS_UID]);
            if (is_array($obj[Config::$USERS_UID])) {
                $uids = $obj[Config::$USERS_UID];
            }
            $name = "";
            if (array_key_exists(Config::$USERS_NAME, $obj)) {
                $name = $obj[Config::$USERS_NAME];
            }
            $email = "";
            if (array_key_exists(Config::$USERS_EMAIL, $obj)) {
                $email = $obj[Config::$USERS_EMAIL];
            }
            $admin = Config::$USERS_ADMIN_DEFAULT_VALUE;
            if (array_key_exists(Config::$USERS_ADMIN, $obj)) {
                $admin = $obj[Config::$USERS_ADMIN];
            }
            foreach ($uids as $uid) {
                $user = new User();
                $user->setUid($uid);
                $user->setName($name);
                $user->setEmail($email);
                $user->setAdmin($admin);
                if ($manager->storeUser($user)) {
                    print "user ".$uid." was created\n";
                } else {
                    print "user ".$uid." was updated\n";
                }
                array_push($ids, $user->getId());
            }
        }
        foreach ($manager->getUsers() as $user) {
            if (!in_array($user->getId(), $ids)) {
                if ($manager->deleteUser($user->getId())) {
                    print "user ".$user->getUid()." was deleted\n";
                } else {
                    print "user ".$user->getUid()." wasn't deleted\n";
                }
            }
        }
        break;

  default:
    die("option -c has unknown value\n");
    break;
}

?>
