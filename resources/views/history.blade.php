<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <!-- <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet"> -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Noto+Sans+SC">

        <!-- Styles -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">    

        <!-- bootstrap js -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        <style>
            body {
                font-family: 'Noto Sans SC', sans-serif;
            }
        </style>
    </head>
    <body>
        <div class="container justify-center min-h-screen">
            <div class="my-4 d-flex">
                <h4>{{ $chat->title }} 飞机群</h4>
                <div class="ms-auto d-flex">
                    <h4>{{ date("l, Y-m-d", time()) }}</h4>
                    {!! $link !!}
                    {!! $exportUrl !!}
                </div>
            </div>
            <div class="card">
                <div class="card-header text-uppercase fw-bolder">
                    入款 ( {{ count($deposits) }} 笔)
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">轮班</th>
                                <th scope="col">金额</th>
                                <th scope="col">操作人</th>
                                <th scope="col">时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ( $deposits as $deposit )
                            <tr>
                                <td>{{ $deposit->id }}</td>
                                <td>{{ $deposit->shift_id }}</td>
                                <td>{{ $deposit->amount }}</td>
                                <td><a target="_blank" href="https://t.me/{{ $deposit->user->username }}">{{ $deposit->user->username }}</a></td>
                                <td>{{ $deposit->created_at }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header text-uppercase fw-bolder">
                    下发 ( {{ count($issueds) }} 笔)
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">轮班</th>
                                <th scope="col">金额</th>
                                <th scope="col">操作人</th>
                                <th scope="col">时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ( $issueds as $issued )
                            <tr>
                                <td>{{ $issued->id }}</td>
                                <td>{{ $issued->shift_id }}</td>
                                <td>{{ $issued->amount }}</td>
                                <td><a target="_blank" href="https://t.me/{{ $issued->user->username }}">{{ $issued->user->username }}</a></td>
                                <td>{{ $issued->created_at }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card mt-5">
                <div class="card-header text-uppercase fw-bolder">详情</div>
                <div class="card-body">
                    <table class="table">
                        <tbody>
                            <tr>
                                <th scope="row">1</th>
                                <td>入款总计</td>
                                <td>{{ $deposits_amount }}</td>
                            </tr>
                            <tr>
                                <th scope="row">2</th>
                                <td>费率</td>
                                <td>{{ $rate }}</td>
                            </tr>
                            <tr>
                                <th scope="row">3</th>
                                <td>应下发</td>
                                <td>{{ $deposits_amount * (1 - ($rate / 100)) }}</td>
                            </tr>
                            <tr>
                                <th scope="row">4</th>
                                <td>总下发</td>
                                <td>{{ $issueds_amount }}</td>
                            </tr>
                            <tr>
                                <th scope="row">5</th>
                                <td>未下发</td>
                                <td>{{ ($deposits_amount * (1 - ($rate / 100))) - $issueds_amount }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>
