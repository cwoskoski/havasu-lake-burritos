@props([
    'remainingSaturday' => 25,
    'remainingSunday' => 30,
    'updateInterval' => 30000, // 30 seconds
    'showDayDetails' => true
])

<div
    x-data="availabilityCounter"
    x-init="init"
    class="bg-gradient-to-r from-lake-blue-600 to-lake-blue-700 text-white rounded-xl p-4 shadow-lg"
>
    <div class="flex items-center justify-center space-x-2 mb-2">
        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse" x-show="isActive"></div>
        <div class="w-2 h-2 bg-red-400 rounded-full animate-pulse" x-show="!isActive" x-cloak></div>
        <span class="text-sm font-medium">Live Availability</span>
    </div>

    <div class="text-center">
        @if($showDayDetails)
            <div class="grid grid-cols-2 gap-4 sm:gap-6">
                <!-- Saturday -->
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-bold" x-text="saturday">{{ $remainingSaturday }}</div>
                    <div class="text-xs sm:text-sm opacity-90">Saturday</div>
                    <div class="text-xs opacity-75">burritos left</div>
                </div>

                <!-- Sunday -->
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-bold" x-text="sunday">{{ $remainingSunday }}</div>
                    <div class="text-xs sm:text-sm opacity-90">Sunday</div>
                    <div class="text-xs opacity-75">burritos left</div>
                </div>
            </div>
        @else
            <div class="text-3xl sm:text-4xl font-bold mb-1" x-text="total">{{ $remainingSaturday + $remainingSunday }}</div>
            <div class="text-sm opacity-90">Total Burritos Available</div>
        @endif

        <!-- Status message -->
        <div class="mt-3 text-xs opacity-90" x-text="statusMessage">
            Updated just now
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('availabilityCounter', () => ({
            saturday: {{ $remainingSaturday }},
            sunday: {{ $remainingSunday }},
            isActive: true,
            statusMessage: 'Updated just now',
            updateInterval: {{ $updateInterval }},

            get total() {
                return this.saturday + this.sunday;
            },

            init() {
                this.startUpdating();
            },

            async fetchAvailability() {
                try {
                    const response = await fetch('/api/availability');
                    if (response.ok) {
                        const data = await response.json();
                        this.saturday = data.saturday || 0;
                        this.sunday = data.sunday || 0;
                        this.isActive = data.isActive || false;
                        this.updateStatus('Updated just now');
                        return true;
                    }
                } catch (error) {
                    console.error('Failed to fetch availability:', error);
                    this.isActive = false;
                    this.updateStatus('Update failed');
                    return false;
                }
                return false;
            },

            updateStatus(message) {
                this.statusMessage = message;

                // Update timestamp after a few seconds
                setTimeout(() => {
                    const now = new Date();
                    const timeString = now.toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    this.statusMessage = `Updated at ${timeString}`;
                }, 2000);
            },

            startUpdating() {
                // Update immediately
                this.fetchAvailability();

                // Then update at intervals
                setInterval(() => {
                    this.fetchAvailability();
                }, this.updateInterval);

                // Also update when page becomes visible again
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        this.fetchAvailability();
                    }
                });
            }
        }))
    });
</script>