@props(['step', 'verified' => false])
<ol aria-label="Installation progress" class="mb-6 flex flex-wrap gap-x-5 gap-y-2 text-sm">
    <li @if($step === 1) aria-current="step" class="font-semibold" @endif>1. Database @if($verified)<span class="ui-help">· Verified</span>@endif</li>
    <li @if($step === 2) aria-current="step" class="font-semibold" @else class="ui-help" @endif>2. Administrator @if($step === 1)<span>· Pending</span>@endif</li>
</ol>
