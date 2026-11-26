@switch($status)
    @case('unpaid')Unpaid@break
    @case('partial')Partial@break
    @case('paid')Paid@break
    @case('void')Void@break
    @default{{ $status }}
@endswitch
