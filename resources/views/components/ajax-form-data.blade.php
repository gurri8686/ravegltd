<script type="text/javascript">
    $(document).ready(function () {
        $('#{{$formId}}').submit(function () {
            submitAjaxFormData('{{$formId}}', "{{$route}}", "{{$method}}");
            return false;
        });
    });
</script>
