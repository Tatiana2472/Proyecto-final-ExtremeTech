{{-- Estilos compartidos por todos los PDF. DomPDF solo admite CSS sencillo. --}}
<style>
    @page { margin: 26px 32px; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        color: #23303f;
        margin: 0;
    }

    h1 { font-size: 17px; margin: 0 0 3px; color: #10161d; }
    h2 { font-size: 12px; margin: 18px 0 6px; color: #10161d; }

    .encabezado {
        border-bottom: 2px solid #0ea5e9;
        padding-bottom: 10px;
        margin-bottom: 14px;
    }

    .encabezado td { vertical-align: top; }

    .gris { color: #6b7889; }
    .pequeno { font-size: 9px; }
    .derecha { text-align: right; }
    .centro { text-align: center; }
    .negrita { font-weight: bold; }

    table { width: 100%; border-collapse: collapse; }

    table.datos td { padding: 2px 0; }

    table.listado { margin-top: 6px; }

    table.listado th {
        background: #10161d;
        color: #fff;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 6px 7px;
        text-align: left;
    }

    table.listado td {
        padding: 6px 7px;
        border-bottom: 1px solid #e3e8ef;
    }

    table.listado tr.par td { background: #f7f9fc; }

    table.listado tfoot td {
        background: #eef2f7;
        font-weight: bold;
        border-top: 2px solid #0ea5e9;
        border-bottom: none;
    }

    .caja {
        border: 1px solid #e3e8ef;
        background: #f7f9fc;
        padding: 8px 10px;
    }

    .totales { width: 250px; float: right; margin-top: 8px; }
    .totales td { padding: 4px 6px; }
    .totales tr.final td {
        border-top: 2px solid #0ea5e9;
        font-size: 12px;
        font-weight: bold;
        color: #10161d;
    }

    .indicadores { margin: 8px 0 4px; }
    .indicadores td {
        width: 25%;
        border: 1px solid #e3e8ef;
        background: #f7f9fc;
        padding: 8px;
        text-align: center;
    }
    .indicadores .valor { font-size: 13px; font-weight: bold; color: #10161d; }
    .indicadores .etiqueta { font-size: 8px; text-transform: uppercase; color: #6b7889; }

    .sello {
        display: inline-block;
        padding: 3px 9px;
        border-radius: 9px;
        font-size: 9px;
        font-weight: bold;
    }
    .sello-verde { background: #d6f5e6; color: #14663f; }
    .sello-gris  { background: #e6eaf0; color: #4c5a6b; }

    .pie {
        position: fixed;
        bottom: -14px;
        left: 0;
        right: 0;
        font-size: 8px;
        color: #8c98a8;
        border-top: 1px solid #e3e8ef;
        padding-top: 4px;
    }
</style>
