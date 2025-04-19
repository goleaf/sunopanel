@props([
    'id' => 'chart-' . \Illuminate\Support\Str::random(6),
    'title' => '',
    'description' => '',
    'type' => 'bar', // bar, line, pie, doughnut
    'labels' => '[]', // JSON array of labels
    'datasets' => '[]', // JSON array of datasets
    'options' => '{}', // JSON of chart options
    'height' => '200px',
    'loading' => false,
])

<div 
    x-data="{
        chart: null,
        labels: {{ $labels }},
        datasets: {{ $datasets }},
        options: {{ $options }},
        init() {
            this.$nextTick(() => {
                const ctx = document.getElementById('{{ $id }}').getContext('2d');
                
                // Add default options based on theme
                const defaultOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: document.querySelector('html').getAttribute('data-theme') === 'dark' 
                                    ? '#e5e7eb' 
                                    : '#374151'
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: document.querySelector('html').getAttribute('data-theme') === 'dark' 
                                    ? '#9ca3af' 
                                    : '#6b7280'
                            },
                            grid: {
                                color: document.querySelector('html').getAttribute('data-theme') === 'dark' 
                                    ? 'rgba(156, 163, 175, 0.1)' 
                                    : 'rgba(107, 114, 128, 0.1)'
                            }
                        },
                        y: {
                            ticks: {
                                color: document.querySelector('html').getAttribute('data-theme') === 'dark' 
                                    ? '#9ca3af' 
                                    : '#6b7280'
                            },
                            grid: {
                                color: document.querySelector('html').getAttribute('data-theme') === 'dark' 
                                    ? 'rgba(156, 163, 175, 0.1)' 
                                    : 'rgba(107, 114, 128, 0.1)'
                            }
                        }
                    }
                };
                
                // Merge provided options with default
                const mergedOptions = {...defaultOptions, ...this.options};
                
                this.chart = new Chart(ctx, {
                    type: '{{ $type }}',
                    data: {
                        labels: this.labels,
                        datasets: this.datasets
                    },
                    options: mergedOptions
                });
                
                // Watch for theme changes
                this.$watch('theme', () => {
                    this.chart.destroy();
                    this.init();
                });
            });
        }
    }"
    {{ $attributes->merge(['class' => 'card bg-base-100 shadow-lg']) }}
>
    <div class="card-body">
        @if($title)
            <h3 class="card-title">{{ $title }}</h3>
        @endif
        
        @if($description)
            <p class="text-sm text-base-content/70">{{ $description }}</p>
        @endif
        
        <div class="mt-4" style="height: {{ $height }}">
            @if($loading)
                <div class="w-full h-full flex items-center justify-center">
                    <span class="loading loading-spinner loading-lg text-primary"></span>
                </div>
            @else
                <canvas id="{{ $id }}"></canvas>
            @endif
        </div>
        
        {{ $slot }}
    </div>
</div> 