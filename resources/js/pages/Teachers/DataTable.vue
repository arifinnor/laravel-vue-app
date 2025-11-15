<script setup lang="ts">
import {
    useVueTable,
    getCoreRowModel,
    type ColumnDef,
} from '@tanstack/vue-table';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Teacher } from './columns';

interface Props {
    data: Teacher[];
    columns: ColumnDef<Teacher>[];
}

const props = defineProps<Props>();

const table = useVueTable({
    get data() {
        return props.data;
    },
    get columns() {
        return props.columns;
    },
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    pageCount: -1,
});
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-sidebar-border/60 shadow-sm dark:border-sidebar-border">
        <Table>
            <TableHeader>
                <TableRow
                    v-for="headerGroup in table.getHeaderGroups()"
                    :key="headerGroup.id"
                    class="bg-muted/20"
                >
                    <TableHead
                        v-for="header in headerGroup.headers"
                        :key="header.id"
                        :class="[
                            'px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground',
                            header.id === 'actions' ? 'text-right' : '',
                        ]"
                    >
                        <template v-if="!header.isPlaceholder">
                            <template
                                v-if="
                                    typeof header.column.columnDef.header ===
                                    'function'
                                "
                            >
                                <component
                                    :is="
                                        header.column.columnDef.header({
                                            column: header.column,
                                            header: header,
                                            table: table,
                                        })
                                    "
                                />
                            </template>
                            <template v-else>
                                {{ header.column.columnDef.header }}
                            </template>
                        </template>
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <template v-if="table.getRowModel().rows?.length">
                    <TableRow
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        class="bg-background transition hover:bg-muted/30"
                    >
                        <TableCell
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                            :class="[
                                'px-4 py-3 text-sm text-foreground',
                                cell.column.id === 'actions'
                                    ? 'text-right'
                                    : '',
                            ]"
                        >
                            <template
                                v-if="
                                    typeof cell.column.columnDef.cell ===
                                    'function'
                                "
                            >
                                <component
                                    :is="
                                        cell.column.columnDef.cell({
                                            row: row,
                                            column: cell.column,
                                            cell: cell,
                                            getValue: cell.getValue,
                                            renderValue: cell.renderValue,
                                            table: table,
                                        })
                                    "
                                />
                            </template>
                            <template v-else>
                                {{ cell.getValue() }}
                            </template>
                        </TableCell>
                    </TableRow>
                </template>
                <template v-else>
                    <TableRow>
                        <TableCell
                            :colspan="props.columns.length"
                            class="px-4 py-6 text-center text-sm text-muted-foreground"
                        >
                            No teachers found.
                        </TableCell>
                    </TableRow>
                </template>
            </TableBody>
        </Table>
    </div>
</template>

