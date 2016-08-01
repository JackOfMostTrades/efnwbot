<?php
  require_once('DiscordClient.class.php');

  function synchronize_all_user_roles($db) {
    $departments = getDepartments($db);
    $positions = getPositions($db, $departments);

    $botClient = DiscordClient::botClient();

    // Set the user's roles
    $roleIdMap = array();
    foreach ($botClient->doGet("https://discordapp.com/api/guilds/" . EFNW_GUILD_ID . "/roles") as $role) {
      $roleIdMap[$role->name] = $role->id;
    }

    $stmt = $db->prepare('SELECT id,username,discord_userid FROM staff_account WHERE active=1 AND discord_userid IS NOT NULL');
    $stmt->execute(array());
    $accounts = array();
    while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) != NULL) {
      $accounts[$row['discord_userid']] = (object) array(
        'id' => intval($row['id']),
        'username' => $row['username'],
        'discord_userid' => $row['discord_userid']
      );
    }

    $members = $botClient->doGet("https://discordapp.com/api/guilds/" . EFNW_GUILD_ID . "/members?limit=1000");
    foreach ($members as $member) {
      if (isset($member->user->bot) && $member->user->bot) { continue; }
      if (!array_key_exists($member->user->id, $accounts)) {
        if (defined('DEBUG')) {
          echo "WARNING: Extra user is joined to the server: " . $member->user->username . "\n";
        }
        continue;
      }

      $accounts[$member->user->id]->roles = $member->roles;
    }

    foreach ($accounts as $account) {
      if (!isset($account->roles)) {
        if (defined('DEBUG')) {
          echo "WARNING: Staff account is not added to the server: " . $account->username . "\n";
        }
        continue;
      }
      synchronize_user_roles_internal($botClient, $db, $positions, $roleIdMap, $account);
    }
  }

  function synchronize_user_roles($db, $staff_account_id) {
    $departments = getDepartments($db);
    $positions = getPositions($db, $departments);
    $botClient = DiscordClient::botClient();

    // Set the user's roles
    $roleIdMap = array();
    foreach ($botClient->doGet("https://discordapp.com/api/guilds/" . EFNW_GUILD_ID . "/roles") as $role) {
      $roleIdMap[$role->name] = $role->id;
    }

    $stmt = $db->prepare('SELECT id,username,discord_userid FROM staff_account WHERE id=?');
    $stmt->execute(array($staff_account_id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row == NULL) {
      throw new Exception("Unable to fetch user.");
    }
    $account = (object) array(
      'id' => intval($row['id']),
      'username' => $row['username'],
      'discord_userid' => $row['discord_userid']
    );

    $member = $botClient->doGet("https://discordapp.com/api/guilds/" . EFNW_GUILD_ID . "/members/$discord_userid");
    if ($member == NULL) {
      if (defined('DEBUG')) {
        echo "WARNING: Unable to find user: $discord_userid\n";
      }
      return;
    }
    $account->roles = $member->roles;

    synchronize_user_roles_internal($botClient, $db, $positions, $roleIdMap, $account);
  }

  function synchronize_user_roles_internal($botClient, $db, $positions, $roleIdMap, $account) {
    $staffer_positions = array();
    $stmt = $db->prepare('SELECT staff_position_id FROM staff_account_position_map WHERE staff_account_id=?');
    $stmt->execute(array($account->id));
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $pos = $positions[intval($row['staff_position_id'])];
      array_push($staffer_positions, $pos);
    }

    $ROLE_MAPS = array(
      "Chair" => "Chairs",
      "Design" => "Design",
      "Events" => "Events",
      "Guest Relations" => "Guest Relations",
      "Hotel" => "Hotel",
      "Human Resources" => "Human Resources",
      "Information Technology" => "Information Technology",
      "Operations" => "Operations",
      "Productions" => "Productions",
      "Public Relations" => "Public Relations",
      "Royal Guard" => "Royal Guard",
      "Pegasi Board" => "Chairs"
    );
    $roles = array();
    foreach ($staffer_positions as $pos) {
      $root_department = $pos->department;
      while ($root_department->parent != NULL) {
        $root_department = $root_department->parent;
      }
      if (!array_key_exists($root_department->name, $ROLE_MAPS)) {
        throw new Exception("Role to department mapping doesn't exist: " . $root_department->name);
      }
      $role = $ROLE_MAPS[$root_department->name];
      $role = $roleIdMap[$role];
      array_push($roles, $role);

      if ($pos->name != 'Co-Chair' && $pos->name != 'President' && $pos->position_type == 'Director') {
        array_push($roles, $roleIdMap['Directors']);
      }
    }
    if ($account->username == 'JackOfMostTrades') {
      array_push($roles, $roleIdMap['Admin']);
    }

    $roles = array_unique($roles);
    sort($roles);

    $current_roles = array_unique($account->roles);
    sort($current_roles);

    if ($roles == $current_roles) {
      if (defined('DEBUG')) {
        echo "Roles for $account->discord_userid are unchanged.\n";
      }
    } else {
      if (defined('DEBUG')) {
        echo "Setting roles for $account->discord_userid from (" . implode(", ", $current_roles) . ") to: " . implode(", ", $roles). "\n";
      }

      $response = $botClient->doPatch("https://discordapp.com/api/guilds/" . EFNW_GUILD_ID . "/members/$account->discord_userid",
        json_encode(array("roles" => $roles)));

      if (defined('DEBUG')) {
        var_dump($response);
      }
    }
  }
