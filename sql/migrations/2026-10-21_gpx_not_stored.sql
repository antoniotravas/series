-- Os ficheiros GPX deixaram de ser guardados: limpar as referências antigas.
UPDATE workouts SET gpx_file = NULL WHERE gpx_file IS NOT NULL;
