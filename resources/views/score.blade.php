<h3>SCORING</h3>
<form action="/ru/score" name="score" method="POST">
    @csrf
    <div class="row">
        <label>M6</label>
        <input name="m[]" value="{{ $m[0] ?? '' }}">
    </div>
    <div class="row">
        <label>M5</label>
        <input name="m[]" value="{{ $m[1] ?? '' }}">
    </div>
    <div class="row">
        <label>M4</label>
        <input name="m[]" value="{{ $m[2] ?? '' }}">
    </div>
    <div class="row">
        <label>M3</label>
        <input name="m[]" value="{{ $m[3] ?? '' }}">
    </div>
    <div class="row">
        <label>M2</label>
        <input name="m[]" value="{{ $m[4] ?? '' }}">
    </div>
    <div class="row">
        <label>M1</label>
        <input name="m[]" value="{{ $m[5] ?? '' }}">
    </div>

    <button type="submit">Скоринг</button>
</form>

