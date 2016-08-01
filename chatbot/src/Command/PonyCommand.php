<?php
  namespace EverfreeNorthwest\Discord\Command;

  class PonyCommand implements Command {
    public function getCommand() {
      return "pony";
    }
    public function getDescription() {
      return "Finds a random pony image matching your search term, for example \"!pony happy\"";
    }

    private function curl_get($ch, $url) {
      curl_setopt($ch, CURLOPT_USERAGENT, 'DiscordBot (https://staff.everfreenw.com, 1.0)');
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $output = curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if ($httpcode != 200) { return NULL; }
      return $output;
    }

    public function execute($message) {
      $ch = curl_init();
      $tag = trim(substr($message->content, strlen('!pony ')));
      $tag = str_replace("\"","", $tag);
      $tag = str_replace("&","", $tag);
      $tag = str_replace(",","", $tag);
      $tag = str_replace(" ", "+", $tag);

      $search = json_decode($this->curl_get($ch, "https://derpibooru.org/search.json?q=safe,$tag"));

      $img = NULL;
      if ($search != NULL && isset($search->total)) {
        $total = $search->total;
        $index = rand(0,$total-1);
        $page = floor($index/15)+1;
        $offset = $index % 15;
        $search = json_decode($this->curl_get($ch, "https://derpibooru.org/search.json?q=safe,$tag&page=$page"));
        if ($search != NULL && isset($search->search) && isset($search->search[$offset])) {
          $image = $search->search[$offset];
          if (in_array("40482", $image->tag_ids)) {
            $img = $image->representations->medium;
          }
        }
      }

      curl_close($ch);

      if ($img != NULL) {
        $message->channel->sendMessage("https:$img");
      } else {
        $message->channel->sendMessage("I have no idea what you're looking for...\nhttps://derpicdn.net/img/2016/4/5/1125252/medium.png");
      }
    }
  }

