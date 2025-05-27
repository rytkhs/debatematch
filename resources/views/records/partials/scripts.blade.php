<script>
    const form = document.getElementById('filterForm');
    const filterInputs = document.querySelectorAll('.filter-select');
    const viewGridButton = document.getElementById('viewGrid');
    const viewListButton = document.getElementById('viewList');
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');

    // フィルター入力変更時にフォームを送信
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            form.submit();
        });
    });

    // フィルターリセットボタンの処理
    document.getElementById('resetFilters').addEventListener('click', function() {
        // 選択されているフィルターをクリア
        document.querySelector('select[name="side"]').value = 'all';
        document.querySelector('select[name="result"]').value = 'all';
        document.querySelector('select[name="sort"]').value = 'newest';
        document.querySelector('input[name="keyword"]').value = '';

        form.submit();
    });

    // ビュー切り替えボタンのイベントリスナー
    viewListButton.addEventListener('click', function() {
        listView.classList.remove('hidden');
        gridView.classList.add('hidden');
        viewListButton.classList.add('active');
        viewGridButton.classList.remove('active');
    });

    viewGridButton.addEventListener('click', function() {
        gridView.classList.remove('hidden');
        listView.classList.add('hidden');
        viewGridButton.classList.add('active');
        viewListButton.classList.remove('active');
    });

    // デフォルトでグリッドビューを表示
    window.addEventListener('DOMContentLoaded', function() {
        gridView.classList.remove('hidden');
        listView.classList.add('hidden');
        viewGridButton.classList.add('active');
        viewListButton.classList.remove('active');
    });
</script>
