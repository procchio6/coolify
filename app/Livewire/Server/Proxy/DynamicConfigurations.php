<?php

namespace App\Livewire\Server\Proxy;

use App\Models\Server;
use Illuminate\Support\Collection;
use Livewire\Component;

class DynamicConfigurations extends Component
{
    public ?Server $server = null;
    public $parameters = [];
    public Collection $contents;
    protected $listeners = ['loadDynamicConfigurations', 'refresh' => '$refresh'];
    protected $rules = [
        'contents.*' => 'nullable|string',
    ];
    public function loadDynamicConfigurations()
    {
        $proxy_path = get_proxy_path();
        $files = instant_remote_process(["mkdir -p $proxy_path/dynamic && ls -1 {$proxy_path}/dynamic"], $this->server);
        $files = collect(explode("\n", $files))->filter(fn ($file) => !empty($file));
        $files = $files->map(fn ($file) => trim($file));
        $files = $files->sort();
        if ($files->contains('coolify.yaml')) {
            $files = $files->filter(fn ($file) => $file !== 'coolify.yaml')->prepend('coolify.yaml');
        }
        $contents = collect([]);
        foreach ($files as $file) {
            $without_extension = str_replace('.', '|', $file);
            $contents[$without_extension] = instant_remote_process(["cat {$proxy_path}/dynamic/{$file}"], $this->server);
        }
        $this->contents = $contents;
    }
    public function mount()
    {
        $this->parameters = get_route_parameters();
        try {
            $this->server = Server::ownedByCurrentTeam()->whereUuid(request()->server_uuid)->first();
            if (is_null($this->server)) {
                return redirect()->route('server.index');
            }
        } catch (\Throwable $e) {
            return handleError($e, $this);
        }
    }
    public function render()
    {
        return view('livewire.server.proxy.dynamic-configurations');
    }
}
