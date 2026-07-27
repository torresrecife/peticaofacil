require('./bootstrap');

const {
    createIcons,
    House,
    Users,
    Building2,
    FileText,
    Database,
    ClipboardList,
    NotebookPen,
    FolderOpen,
    ChartColumn,
} = require('lucide');

document.addEventListener('DOMContentLoaded', function () {
    createIcons({
        attrs: {
            class: 'app-icon',
            width: 18,
            height: 18,
            'stroke-width': 1.8,
        },
        icons: {
            House,
            Users,
            Building2,
            FileText,
            Database,
            ClipboardList,
            NotebookPen,
            FolderOpen,
            ChartColumn,
        },
    });
});
