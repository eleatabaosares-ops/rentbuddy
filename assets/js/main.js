/* RentBuddy - Main Interactive Scripts */

document.addEventListener('DOMContentLoaded', function () {
    // Mobile sidebar toggle
    const toggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 900 &&
                sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) && e.target !== toggle) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Modal open/close
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-modal-open');
            const m = document.getElementById(id);
            if (m) m.classList.add('open');
        });
    });
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('.modal-backdrop').classList.remove('open'));
    });
    document.querySelectorAll('.modal-backdrop').forEach(bd => {
        bd.addEventListener('click', (e) => { if (e.target === bd) bd.classList.remove('open'); });
    });

    // Confirm delete
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            if (!confirm(el.getAttribute('data-confirm'))) e.preventDefault();
        });
    });

    // Table search (client-side quick filter)
    document.querySelectorAll('[data-table-search]').forEach(input => {
        input.addEventListener('input', () => {
            const table = document.querySelector(input.getAttribute('data-table-search'));
            if (!table) return;
            const q = input.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach(tr => {
                tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });

    // Tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const group = tab.parentElement;
            group.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const target = tab.getAttribute('data-tab');
            document.querySelectorAll('[data-tab-content]').forEach(c => {
                c.style.display = c.getAttribute('data-tab-content') === target ? '' : 'none';
            });
        });
    });

    // Demo payment simulation
    const payBtn = document.getElementById('payNowBtn');
    if (payBtn) {
        payBtn.addEventListener('click', function () {
            const form = document.getElementById('paymentForm');
            if (form) form.submit();
        });
    }
});