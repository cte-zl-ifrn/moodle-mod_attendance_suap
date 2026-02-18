<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Portuguese (Brazil) language strings for mod_attendance_suap
 *
 * @package    mod_attendance_suap
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Frequência SUAP';
$string['modulename'] = 'Frequência SUAP';
$string['modulenameplural'] = 'Frequências SUAP';
$string['modulename_help'] = 'Módulo de controle de frequência baseado em completude de atividades, integrado com SUAP.';

// Capabilities.
$string['attendance_suap:addinstance'] = 'Adicionar uma nova instância de Frequência SUAP';
$string['attendance_suap:manage'] = 'Gerenciar dias, aulas e módulos';
$string['attendance_suap:viewmatrix'] = 'Visualizar matriz de frequência';
$string['attendance_suap:viewprogress'] = 'Visualizar progresso';

// General.
$string['name'] = 'Nome';
$string['description'] = 'Descrição';
$string['intro'] = 'Introdução';

// Days.
$string['days'] = 'Dias';
$string['day'] = 'Dia';
$string['addday'] = 'Adicionar dia';
$string['editday'] = 'Editar dia';
$string['deleteday'] = 'Excluir dia';
$string['data_inicio'] = 'Data de início';
$string['data_fim'] = 'Data de término';
$string['confirmdeleteday'] = 'Tem certeza que deseja excluir este dia?';

// Lessons.
$string['lessons'] = 'Aulas';
$string['lesson'] = 'Aula';
$string['addlesson'] = 'Adicionar aula';
$string['editlesson'] = 'Editar aula';
$string['deletelesson'] = 'Excluir aula';
$string['plano'] = 'Plano de aula';
$string['confirmdeletelesson'] = 'Tem certeza que deseja excluir esta aula?';

// Modules.
$string['modules'] = 'Módulos';
$string['module'] = 'Módulo';
$string['addmodule'] = 'Adicionar módulo';
$string['deletemodule'] = 'Remover módulo';
$string['selectmodule'] = 'Selecionar atividade/recurso';
$string['confirmdeletemodule'] = 'Tem certeza que deseja remover este módulo?';

// Progress.
$string['progress'] = 'Progresso';
$string['completed'] = 'Completo';
$string['incomplete'] = 'Incompleto';
$string['notstarted'] = 'Não iniciado';
$string['inprogress'] = 'Em andamento';

// Matrix.
$string['matrix'] = 'Matriz de Frequência';
$string['viewmatrix'] = 'Ver Matriz';
$string['student'] = 'Aluno';
$string['group'] = 'Grupo';
$string['total'] = 'Total';
$string['export'] = 'Exportar';
$string['exportcsv'] = 'Exportar CSV';
$string['exporthtml'] = 'Exportar HTML';

// Settings.
$string['tendencia_threshold'] = 'Limite de tendência';
$string['tendencia_threshold_desc'] = 'Limite de notificação para análise de tendência (padrão: 0.90)';
$string['notification_roles'] = 'Perfis para notificação';
$string['notification_roles_desc'] = 'Perfis que recebem notificações (separados por vírgula)';

// Notifications.
$string['notification_subject'] = 'Alerta de frequência';
$string['notification_message'] = 'Seu progresso de frequência está abaixo do esperado. Atual: {$a->current}, Esperado: {$a->expected}';

// Errors.
$string['error_daterange'] = 'Data de término deve ser posterior à data de início';
$string['error_nodays'] = 'Nenhum dia configurado';
$string['error_nolessons'] = 'Nenhuma aula configurada';
$string['error_nomodules'] = 'Nenhum módulo configurado';

// Tasks.
$string['notificationtask'] = 'Enviar notificações de frequência';

// Help.
$string['tendencia_threshold_help'] = 'Valor limite (0-1) para disparo de notificação. Se o progresso atual de um aluno estiver abaixo desse limite multiplicado pelo progresso esperado, ele receberá uma notificação. O padrão é 0.90 (90%).';
