{if $pagination.totalPages > 1}
    <nav class="pagination-nav" aria-label="Пагинация статей">
        <ul class="pagination-list">
            {* Previous page button *}
            {if $pagination.currentPage > 1}
                <li class="page-item">
                    <a href="{$pagination.baseUrl}?page={$pagination.currentPage - 1}&sort={$pagination.currentSort}" class="page-link prev-link">
                        &laquo; Предыдущая
                    </a>
                </li>
            {/if}

            {* Page numbers *}
            {for $p = 1 to $pagination.totalPages}
                {if $p == $pagination.currentPage}
                    <li class="page-item active" aria-current="page">
                        <span class="page-link">{$p}</span>
                    </li>
                {elseif $p == 1 || $p == $pagination.totalPages || ($p >= $pagination.currentPage - 2 && $p <= $pagination.currentPage + 2)}
                    <li class="page-item">
                        <a href="{$pagination.baseUrl}?page={$p}&sort={$pagination.currentSort}" class="page-link">
                            {$p}
                        </a>
                    </li>
                {elseif $p == $pagination.currentPage - 3 || $p == $pagination.currentPage + 3}
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                {/if}
            {/for}

            {* Next page button *}
            {if $pagination.currentPage < $pagination.totalPages}
                <li class="page-item">
                    <a href="{$pagination.baseUrl}?page={$pagination.currentPage + 1}&sort={$pagination.currentSort}" class="page-link next-link">
                        Следующая &raquo;
                    </a>
                </li>
            {/if}
        </ul>
    </nav>
{/if}
