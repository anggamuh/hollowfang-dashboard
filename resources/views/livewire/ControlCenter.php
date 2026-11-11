    <?php

namespace App\Livewire;

use Livewire\Component;

class ControlCenter extends Component
{
    public string $section = 'overview';

    public function changeSection(string $section): void
    {
        $allowed = ['overview','monitoring','webhooks','gateway','moderation','members','music','events','logs','settings'];

        if (in_array($section, $allowed, true)) {
            $this->section = $section;
        }
    }

    public function render()
    {
        return view('livewire.control-center');
    }
}
