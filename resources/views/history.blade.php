<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

        <!-- Styles -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">    

        <!-- bootstrap js -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        <style>
            body {
                font-family: 'Nunito', sans-serif;
            }
        </style>
    </head>
    <body>
        <div class="container justify-center min-h-screen">
            <div class="my-4 d-flex">
                <h4>Nhóm {{ $chat->title }}</h4>
                <div class="ms-auto d-flex">
                    <h4>{{ date("l, Y-m-d", time()) }}</h4>
                    {!! $link !!}
                    {!! $exportUrl !!}
                </div>
            </div>
            <div class="card">
                <div class="card-header text-uppercase fw-bolder">
                    Nhập khoản ( {{ $deposit_count }} đơn)
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Ca làm việc</th>
                                <th scope="col">Thời gian</th>
                                <th scope="col">Người thực hiện</th>
                                <th scope="col">Số tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ( $deposits_per_shift as $deposits )
                            @foreach ( $deposits as $deposit )
                            <tr>
                                <td>{{ $deposit->shift_id }}</td>
                                <td>{{ $deposit->created_at }}</td>
                                <td>{{ $deposit->user()->first()->username }}</td>
                                <td>{{ $deposit->amount }}</td>
                            </tr>
                            @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header text-uppercase fw-bolder">
                    Xuất khoản ( {{ $issued_count }} đơn)
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Ca làm việc</th>
                                <th scope="col">Thời gian</th>
                                <th scope="col">Người thực hiện</th>
                                <th scope="col">Số tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ( $issueds_per_shift as $issueds )
                            @foreach ( $issueds as $issued )
                            <tr>
                                <td>{{ $issued->shift_id }}</td>
                                <td>{{ $issued->created_at }}</td>
                                <td>{{ $issued->user()->first()->username }}</td>
                                <td>{{ $issued->amount }}</td>
                            </tr>
                            @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card mt-5">
                <div class="card-header text-uppercase fw-bolder">
                    Chi tiết
                </div>
                <div class="card-body">
                    <table class="table">
                        <tbody>
                            <tr>
                                <th scope="row">1</th>
                                <td>Tổng số đơn nhập khoản</td>
                                <td>{{$deposit_count}}</td>
                            </tr>
                            <tr>
                                <th scope="row">2</th>
                                <td>Tổng số tiền nhập khoản</td>
                                <td>{{$deposit_amount}}</td>
                            </tr>
                            <tr>
                                <th scope="row">3</th>
                                <td>Tổng số đơn xuất khoản</td>
                                <td>{{$issued_count}}</td>
                            </tr>
                            <tr>
                                <th scope="row">4</th>
                                <td>Tổng số tiền chưa xuất khoản</td>
                                <td>{{$deposit_amount - $issued_amount}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>
