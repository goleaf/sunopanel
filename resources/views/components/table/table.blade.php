@props(['header', 'body'])

<div class="flex flex-col">
    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
            <div class="shadow overflow-hidden border-b border-base-300 sm:rounded-lg">
                <table class="min-w-full divide-y divide-base-300">
                    <thead class="bg-base-200">
                        <tr>
                            {{ $header }}
                        </tr>
                    </thead>
                    <tbody class="bg-base-100 divide-y divide-base-300">
                        {{ $body }}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> 