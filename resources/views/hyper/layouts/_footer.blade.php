<!-- Footer Start -->
<footer class="hyper-footer">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="footer-links shyapi-footer-brand">
                    <strong>{{ dujiaoka_config_get('text_logo', 'ShyAPI') }} Shop</strong>
                    <span>自动发卡与合伙人分销中心</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="text-md-right footer-links d-none d-md-block">
                    @if(trim((string) dujiaoka_config_get('footer')) !== '')
                        {!! dujiaoka_config_get('footer') !!}
                    @else
                        <a href="https://code.shyapi.top/docs/" target="_blank" rel="noopener">接入文档</a>
                        <span>·</span>
                        <a href="https://code.shyapi.top" target="_blank" rel="noopener">前往控制台充值</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="back-to-top">
        <button class=" btn btn-primary" id="back-to-top">
            <i class="dripicons-chevron-up"></i>
        </button>
    </div>
</footer>
<!-- end Footer -->
