<?php

/**
 * StpPortsController.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2021 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Http\Controllers\Table;

use App\Facades\DeviceCache;
use App\Models\PortStp;
use App\Models\Stp;
use Illuminate\Support\Facades\Blade;
use LibreNMS\Util\Mac;

class PortStpController extends TableController
{
    public function rules()
    {
        return [
            'device_id' => 'int',
            'vlan' => 'int',
        ];
    }

    protected function filterFields($request): array
    {
        return [
            'device_id',
            'vlan' => function ($query, $vlan) {
                $query->where(function ($query) use ($vlan) {
                    $query->where('vlan', $vlan)->when($vlan == 1, function ($query) {
                        return $query->orWhereNull('vlan');
                    })->when($vlan === null, function ($query) {
                        return $query->orWhere('vlan', 1);
                    });
                });
            },
        ];
    }

    protected function sortFields($request)
    {
        return [
            'device_id',
            'vlan',
            'port_id',
            'priority',
            'state',
            'enable',
            'pathCost',
            'designatedRoot',
            'designatedCost',
            'designatedBridge',
            'designatedPort',
            'forwardTransitions',
        ];
    }

    protected function baseQuery($request)
    {
        return PortStp::query()->with('port');
    }

    /**
     * @param  PortStp  $stpPort
     */
    public function formatItem($stpPort)
    {
        $drMac = Mac::parse($stpPort->designatedRoot);
        $dbMac = Mac::parse($stpPort->designatedBridge);

        $dr = DeviceCache::get(Stp::where('bridgeAddress', $stpPort->designatedRoot)->whereNot('bridgeAddress', '000000000000')->value('device_id'));
        $db = DeviceCache::get(Stp::where('bridgeAddress', $stpPort->designatedBridge)->whereNot('bridgeAddress', '')->value('device_id'));

        return [
            'port_id' => Blade::render('<x-port-link :port="$port">{{ $port->getShortLabel() }}</x-port-link><br /> {{ $port->getDescription() }}', ['port' => $stpPort->port]),
            'vlan' => $stpPort->vlan ?: 1,
            'priority' => $stpPort->priority,
            'state' => $stpPort->state,
            'enable' => $stpPort->enable,
            'pathCost' => $stpPort->pathCost,
            'designatedRoot' => $drMac->readable(),
            'designatedRoot_vendor' => $drMac->vendor(),
            'designatedRoot_device' => Blade::render('<x-device-link :device="$device"/>', ['device' => $dr]),
            'designatedCost' => $stpPort->designatedCost,
            'designatedBridge' => $dbMac->readable(),
            'designatedBridge_vendor' => $dbMac->vendor(),
            'designatedBridge_device' => Blade::render('<x-device-link :device="$device"/>', ['device' => $db]),
            'designatedPort' => $stpPort->designatedPort,
            'forwardTransitions' => $stpPort->forwardTransitions,
        ];
    }
}
