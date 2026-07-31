/**
 * Copyright (c) 2003-2019, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

// This file contains style definitions that can be used by CKEditor plugins.
//
// The most common use for it is the "stylescombo" plugin which shows the Styles drop-down
// list containing all styles in the editor toolbar. Other plugins, like
// the "div" plugin, use a subset of the styles for their features.
//
// If you do not have plugins that depend on this file in your editor build, you can simply
// ignore it. Otherwise it is strongly recommended to customize this file to match your
// website requirements and design properly.
//
// For more information refer to: https://ckeditor.com/docs/ckeditor4/latest/guide/dev_styles.html#style-rules

CKEDITOR.stylesSet.add( 'peticao_juridica', [
	{ name: 'Titulo Principal', element: 'h2', attributes: { 'class': 'peticao-titulo-principal' } },
	{ name: 'Subtitulo', element: 'h3', attributes: { 'class': 'peticao-subtitulo' } },
	{ name: 'Corpo da Peticao', element: 'p', attributes: { 'class': 'peticao-corpo' } },
	{ name: 'Fundamentacao', element: 'p', attributes: { 'class': 'peticao-fundamentacao' } },
	{ name: 'Pedido', element: 'p', attributes: { 'class': 'peticao-pedido' } },
	{ name: 'Assinatura', element: 'p', attributes: { 'class': 'peticao-assinatura' } },
	{ name: 'Observacao Interna', element: 'p', attributes: { 'class': 'peticao-observacao' } },
	{ name: 'Destaque', element: 'span', attributes: { 'class': 'peticao-destaque' } },
	{ name: 'Tabela Compacta', element: 'table', attributes: { 'class': 'peticao-tabela-compacta', cellpadding: '6', cellspacing: '0', border: '1' } },
	{ name: 'Lista de Itens', element: 'ul', attributes: { 'class': 'peticao-lista' } }
] );

