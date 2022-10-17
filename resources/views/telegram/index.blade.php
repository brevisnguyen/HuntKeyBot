<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HuntKeyBot</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <nav class="container bg-gray-50 border-gray-200 px-2 sm:px-4 py-2 rounded dark:bg-gray-900 mx-auto">
        <div class="flex flex-wrap justify-between items-center mx-auto">
            <p class="text-orange-400 font-extrabold my-auto">群组飞机群</p>
            <div class="w-full md:block md:w-auto " id="navbar-default">
                <ul class="flex flex-col p-2 mt-4 bg-gray-50 rounded-lg border border-gray-100 md:flex-row md:space-x-4 md:mt-0 md:text-sm md:font-medium md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                    <li>
                        <div class="relative mr-8 content-end">
                            <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                                <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path></svg>
                            </div>
                            <input datepicker type="text" class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Select date">
                        </div>
                    </li>
                    <li>
                        <button type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">筛选</button>
                    </li>
                    <li>
                        <button type="button" class="focus:outline-none text-white bg-yellow-400 hover:bg-yellow-500 focus:ring-4 focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2 dark:focus:ring-yellow-900">导出</button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container items-center justify-center mx-auto py-5">
        <div class="overflow-x-auto relative">
            <p class="px-2 font-bold">入款 ( 100 笔)</p>
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="py-3 px-6">操作人</th>
                        <th scope="col" class="py-3 px-6">费率</th>
                        <th scope="col" class="py-3 px-6">金额</th>
                        <th scope="col" class="py-3 px-6">时间</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($deposits as $deposit)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <th class="py-1 px-6">{{$deposit->user->first_name}}</th>
                        <td class="py-1 px-6">{{$deposit->shift->rate}}</td>
                        <td class="py-1 px-6">{{$deposit->amount}}</td>
                        <td class="py-1 px-6">{{$deposit->created_at}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $deposits->links() }}
        </div>
        <div class="overflow-x-auto relative mt-4">
            <p class="px-2 font-bold">下发 ( 100 笔)</p>
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="py-3 px-6">操作人</th>
                        <th scope="col" class="py-3 px-6">费率</th>
                        <th scope="col" class="py-3 px-6">金额</th>
                        <th scope="col" class="py-3 px-6">时间</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($issueds as $issued)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <th class="py-1 px-6">{{$issued->user->first_name}}</th>
                        <td class="py-1 px-6">{{$issued->shift->rate}}</td>
                        <td class="py-1 px-6">{{$issued->amount}}</td>
                        <td class="py-1 px-6">{{$issued->created_at}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $issueds->links() }}
        </div>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>