<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Presence;
use Illuminate\Console\Command;

class PurgerChat extends Command
{
    protected $signature = 'chat:purger';

    protected $description = 'Purge les présences périmées et les conversations de chat inactives';

    public function handle(): int
    {
        $presences = Presence::where('derniere_activite', '<', now()->subHour())->delete();

        $limite = now()->subDays((int) config('chatbot.purge_jours', 90));
        $conversations = Conversation::where('updated_at', '<', $limite)->delete(); // cascade → messages

        $this->components->info("{$presences} présence(s) et {$conversations} conversation(s) purgées.");

        return self::SUCCESS;
    }
}
