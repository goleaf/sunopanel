<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">SunoPanel API Documentation</h1>
    
    <div class="mb-8">
        <p class="mb-4">This documentation provides details about the available API endpoints in the SunoPanel music platform. 
        All API endpoints return data in JSON format and require proper authentication.</p>
        
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" role="alert">
            <p class="font-bold">Authentication</p>
            <p>All API requests require authentication. Include a valid API token in the request headers:</p>
            <pre class="bg-gray-100 p-2 mt-2 rounded">Authorization: Bearer YOUR_API_TOKEN</pre>
        </div>
    </div>
    
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4">Response Format</h2>
        <p class="mb-2">All API responses follow a standard format:</p>
        <pre class="bg-gray-100 p-4 rounded mb-4">
{
  "success": true,
  "data": { ... },
  "message": "Optional message"
}
        </pre>
        <p>For error responses:</p>
        <pre class="bg-gray-100 p-4 rounded">
{
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
        </pre>
    </div>
    
    @foreach($apiEndpoints as $api)
        <div class="mb-12 border-b pb-8" x-data="{ open: false }">
            <div class="flex justify-between items-center cursor-pointer" @click="open = !open">
                <h2 class="text-2xl font-bold">{{ $api['name'] }}</h2>
                <svg :class="{'rotate-180': open}" class="w-6 h-6 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
            
            <p class="mt-2 mb-4 text-gray-600">{{ $api['description'] }}</p>
            
            <div x-show="open" x-transition class="mt-4">
                @foreach($api['endpoints'] as $endpoint)
                    <div class="mb-8 border border-gray-200 rounded-lg overflow-hidden">
                        <div class="flex items-center p-4 bg-gray-50 border-b">
                            <span class="px-3 py-1 rounded {{ 
                                $endpoint['method'] === 'GET' ? 'bg-blue-100 text-blue-800' : 
                                ($endpoint['method'] === 'POST' ? 'bg-green-100 text-green-800' : 
                                ($endpoint['method'] === 'PUT' ? 'bg-yellow-100 text-yellow-800' : 
                                'bg-red-100 text-red-800')) 
                            }} font-mono text-sm mr-3">{{ $endpoint['method'] }}</span>
                            <span class="font-mono text-sm">{{ $endpoint['endpoint'] }}</span>
                        </div>
                        
                        <div class="p-4">
                            <h3 class="font-bold mb-2">{{ $endpoint['description'] }}</h3>
                            
                            @if(count($endpoint['parameters']) > 0)
                                <div class="mt-4">
                                    <h4 class="font-bold text-sm uppercase text-gray-600 mb-2">Parameters</h4>
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead>
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($endpoint['parameters'] as $name => $description)
                                                <tr>
                                                    <td class="px-4 py-2 whitespace-nowrap font-mono text-sm">{{ $name }}</td>
                                                    <td class="px-4 py-2 text-sm">{{ $description }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            
                            <div class="mt-4">
                                <h4 class="font-bold text-sm uppercase text-gray-600 mb-2">Response</h4>
                                <p class="text-sm">{{ $endpoint['response'] }}</p>
                            </div>
                            
                            <div class="mt-4">
                                <h4 class="font-bold text-sm uppercase text-gray-600 mb-2">Example</h4>
                                <pre class="bg-gray-100 p-3 rounded text-sm overflow-auto">
curl -X {{ $endpoint['method'] }} \
     -H "Authorization: Bearer YOUR_API_TOKEN" \
     -H "Content-Type: application/json" \
     {{ $endpoint['method'] === 'GET' ? '' : '-d \'{"example": "data"}\' ' }}{{ $endpoint['endpoint'] }}</pre>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div> 