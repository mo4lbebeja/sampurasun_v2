@if(!empty($kopSuratBase64))
    <div class="kop-surat">
        <img src="{!! $kopSuratBase64 !!}" alt="Kop Surat">
    </div>
@else
    <div class="kop-placeholder">
        KOP SURAT BELUM DIUPLOAD
    </div>
@endif