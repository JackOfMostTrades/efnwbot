<?php
  require_once('../config.php');
  require_once('../login.inc.php');
  require_once('../common.inc.php');
  require_once('DiscordClient.class.php');
  require_once('discord_sync.inc.php');

  $errorMsg = NULL;
  try {
    $db = new PDO('mysql:host='.SQL_HOST.';dbname='.SQL_DATABASE.';charset=utf8', SQL_USER_RW, SQL_PASSWD_RW);
    
    $staffer = NULL;
    $stmt = $db->prepare('SELECT id,discord_userid FROM staff_account WHERE username=?');
    $stmt->execute(array($_SERVER['account_username']));
    if (($row = $stmt->fetch(PDO::FETCH_ASSOC)) == NULL) {
      throw new Exception("Could not fetch user data.");
    }
    $staffer = (object) array(
      'id' => intval($row['id']),
      'discord_userid' => $row['discord_userid']
    );

    $userClient = NULL;
    if (isset($_GET['code'])) {
      $userClient = DiscordClient::userClient($_GET['code']);
    } else {
      DiscordClient::authRedirect();
    }
    if ($userClient == NULL) {
      throw new Exception("Unable to get access token.");
    }
    $response = $userClient->doGet('https://discordapp.com/api/users/@me');
    if ($response == NULL) {
      throw new Exception("Unable to fetch user data.");
    }
    if ($staffer->discord_userid != NULL && $staffer->discord_userid != $response->id) {
      throw new Exception("You have already been added to Discord with a different user.");
    }
    if ($staffer->discord_userid == NULL) {
      $stmt = $db->prepare("UPDATE staff_account SET discord_userid=? WHERE id=?");
      $stmt->execute(array($response->id, $staffer->id));
      $staffer->discord_userid = $response->id;
    }

    // Join the guild
    $botClient = DiscordClient::botClient();
    $userdata = $botClient->doGet("https://discordapp.com/api/guilds/" . EFNW_GUILD_ID . "/members/$staffer->discord_userid");
    if (!isset($userdata->joined_at)) {
      $channels = $botClient->doGet("https://discordapp.com/api/guilds/" . EFNW_GUILD_ID . "/channels");
      foreach ($channels as $chan) {
        if ($chan->name == 'general') {
          $chan_id = $chan->id;
          break;
        }
      }
      if (!isset($chan_id)) {
        throw new Exception("Could not find general channel.");
      }
      $invite = $botClient->doPost("https://discordapp.com/api/channels/$chan_id/invites", array( 'max_uses' => 1 ));
      $userClient->doPost("https://discordapp.com/api/invites/" . $invite->code, array());
    }

    synchronize_user_roles($db, $staffer->id);

  } catch (Exception $e) {
    $errorMsg = $e->getMessage();
  }

?>
<?php include('../header.inc.php'); ?>
<div class="panel panel-default">
  <div class="panel-body">
    <?php
      if ($errorMsg != NULL) { echo $errorMsg; }
      else { echo 'You have been added to the Everfree Northwest Discord server!'; }
    ?>
  </div>
</div>
<?php include('../footer.inc.php'); ?>
