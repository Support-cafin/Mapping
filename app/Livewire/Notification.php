<?php

// app/Livewire/Notification.php
namespace App\Livewire;

use Livewire\Component;

class Notification extends Component
{
    public $message = '';
    public $type = 'info';
    public $show = false;

    protected $listeners = ['notify' => 'showNotification'];

    public function showNotification($data)
    {
        $this->message = $data['message'];
        $this->type = $data['type'];
        $this->show = true;
        
        $this->dispatch('notify-show');
        
        // Auto-hide after 3 seconds
        $this->dispatch('notify-hide', delay: 3000);
    }

    public function hide()
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.notification');
    }
}