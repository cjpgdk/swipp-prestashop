$(document).ready(function () {

    $('.fancybox').fancybox();
});
            function faqOpen(index) {
                document.getElementById('faqtxt_' + index).style.display = "block";
            }
            function faqClose(index) {
                document.getElementById('faqtxt_' + index).style.display = "none";
            }