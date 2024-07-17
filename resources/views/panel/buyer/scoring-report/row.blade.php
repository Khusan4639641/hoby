<tr class="{{
                                    $status === \App\Models\ScoringResult::STATE_USER_INFO_SUCCESS
                                    ?
                                    'alert-success'
                                    :
                                    (
                                        $status === \App\Models\ScoringResult::STATE_USER_INFO_NOT_SUCCESS ||
                                        $status === \App\Models\ScoringResult::STATE_FAILED_RESPONSE
                                        ?
                                        'alert-danger'
                                        :
                                        ''
                                    )
                                    }}">
    <td class="font-weight-bold">{{__('scoring.' . $key . '_text')}}</td>
    <td class="text-right">
                            <span class="badge-pill pb-1 {{
                                    $status === \App\Models\ScoringResult::STATE_USER_INFO_SUCCESS
                                    ?
                                    'badge-success'
                                    :
                                    (
                                        $status === \App\Models\ScoringResult::STATE_USER_INFO_NOT_SUCCESS ||
                                        $status === \App\Models\ScoringResult::STATE_FAILED_RESPONSE
                                        ?
                                        'badge-danger'
                                        :
                                        ''
                                    )
                                    }}">{{ $message }}</span>
    </td>
</tr>
@if($status === \App\Models\ScoringResult::STATE_USER_INFO_NOT_SUCCESS ||
                    $status === \App\Models\ScoringResult::STATE_FAILED_RESPONSE)
    <tr class="{{
                                    $status === \App\Models\ScoringResult::STATE_USER_INFO_SUCCESS
                                    ?
                                    'alert-success'
                                    :
                                    (
                                        $status === \App\Models\ScoringResult::STATE_USER_INFO_NOT_SUCCESS ||
                                        $status === \App\Models\ScoringResult::STATE_FAILED_RESPONSE
                                        ?
                                        'alert-danger'
                                        :
                                        ''
                                    )
                                    }}">
        <td colspan="2" class="text-right">
                            <span class="pb-1 {{
                                    $status === \App\Models\ScoringResult::STATE_USER_INFO_SUCCESS
                                    ?
                                    'text-success'
                                    :
                                    (
                                        $status === \App\Models\ScoringResult::STATE_USER_INFO_NOT_SUCCESS ||
                                        $status === \App\Models\ScoringResult::STATE_FAILED_RESPONSE
                                        ?
                                        'text-danger'
                                        :
                                        ''
                                    )
                                    }}">{{ $error_message }}</span>
        </td>
    </tr>
@endif

