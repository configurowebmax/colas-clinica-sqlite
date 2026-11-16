/**
 * Loader Element Creation
 */
const loader = document.createElement('div')
loader.setAttribute("id", "preloader")
loader.innerHTML = `<div class="lds-ring"><div></div><div></div><div></div><div></div></div>`

/** Show Loader to document */
window.start_loader = function(){
    if(document.querySelector('#preloader') != null)
        document.querySelector('#preloader').remove()

    document.body.appendChild(loader)
}

/** Remove Loader From Document */
window.end_loader = function(){
    if(document.querySelector('#preloader') != null)
        document.querySelector('#preloader').remove()
}

// Remover el evento beforeunload global
/* window.addEventListener('beforeunload', async function(e){
    e.preventDefault()
    start_loader()
}) */

// Solo mostrar loader en navegación intencional
document.addEventListener('DOMContentLoaded', function() {
    // Para links normales
    document.querySelectorAll('a:not([href^="#"])').forEach(link => {
        link.addEventListener('click', function() {
            start_loader();
        });
    });

    // Para forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            if(!form.classList.contains('no-loader')) {
                start_loader();
            }
        });
    });
});

$(function() {
    $('#expenseModal').on('hide.bs.modal', e => {
        var _form = $('#expenseModal form')
        _form[0].reset()
    })
})