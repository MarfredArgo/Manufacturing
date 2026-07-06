
// Sidebar Page Switching
function switchPage(page) {
    document.querySelectorAll('.page-content').forEach(el => {
        el.classList.add('hidden');
    });

    document.getElementById('page-' + page).classList.remove('hidden');

    document.querySelectorAll('.nav-link').forEach(el => {
        el.classList.remove('text-nexora-off-white', 'underline', 'decoration-2', 'underline-offset-2');
        el.classList.add('text-nexora-corporate/80');
    });

    document.querySelector(`[data-page="${page}"]`).classList.add(
        'text-nexora-off-white', 'underline', 'decoration-2', 'underline-offset-2'
    );
    document.querySelector(`[data-page="${page}"]`).classList.remove('text-nexora-corporate/80');
}
//Status Changing Content
function showOrder(index) {

    document.querySelectorAll('[id^="detail-"]').forEach(el => {
        el.classList.add('hidden');
    });

    document.getElementById('detail-' + index).classList.remove('hidden');

    document.querySelectorAll('[id^="card-"]').forEach(el => {
        el.classList.remove('bg-nexora-steel-blue/80');
        el.classList.add('hover:bg-nexora-steel-blue/50', 'hover:-translate-y-[2px]', 'hover:shadow-md');
    });

    const activeCard = document.getElementById('card-' + index);
    activeCard.classList.add('bg-nexora-steel-blue/80');
    activeCard.classList.remove('hover:bg-nexora-steel-blue/50', 'hover:-translate-y-[2px]', 'hover:shadow-md');
}
//Filter Button
function filterOrders(status) {
    currentFilter = status;
    const search = document.getElementById('search-input').value.toLowerCase();
    
    document.querySelectorAll('[id^="card-"]').forEach(card => {
        const matchesStatus = status === 'all' || card.dataset.status === status;
        const matchesSearch = card.dataset.name.toLowerCase().includes(search);

        if (matchesStatus && matchesSearch) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('bg-nexora-corporate', 'text-white');
        btn.classList.add('text-nexora-deep-navy');
    });

    document.querySelector(`[data-filter="${status}"]`).classList.add('bg-nexora-corporate', 'text-white');
    document.querySelector(`[data-filter="${status}"]`).classList.remove('text-nexora-deep-navy');

    reanimateRows();
}
//Table Items Animation
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('.row-animate');
    
    document.querySelectorAll('.row-animate').forEach(row => {
        row.addEventListener('animationend', () => {
            row.classList.add('done');
        });
    });

    rows.forEach((row, i) => {
        setTimeout(() => {
            row.classList.add('animate');
        }, i * 20);
    });

    reanimateRows();
});
function reanimateRows() {
    const visibleRows = document.querySelectorAll('.row-animate:not(.hidden)');

    visibleRows.forEach(row => row.classList.remove('animate', 'done'));

    setTimeout(() => {
        visibleRows.forEach((row, i) => {
            setTimeout(() => row.classList.add('animate'), i * 20);
        });
    }, 20);
}
