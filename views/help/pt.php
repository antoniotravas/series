<?php
$toc = [
    'inicio'     => 'Primeiros passos',
    'registar'   => 'Registar um treino',
    'gpx'        => 'Usar o ficheiro GPX do relógio',
    'analisar'   => 'Analisar um treino',
    'procurar'   => 'Encontrar e organizar treinos',
    'painel'     => 'Painel e recordes',
    'excel'      => 'Importar e exportar',
    'partilhar'  => 'Partilhar treinos',
    'rede'       => 'Rede de atletas',
    'treinador'  => 'Sou treinador',
    'atleta'     => 'Tenho treinador',
    'perfil'     => 'Perfil e conta',
    'faq'        => 'Perguntas frequentes',
];

$body = function (): void { ?>

<section id="inicio" class="help__section">
  <h2>Primeiros passos</h2>
  <p>O Séries é um caderno de treino para quem corre em pista. Registas cada repetição com o seu tempo, e a aplicação calcula médias, ritmos, regularidade e recordes.</p>

  <article class="usecase">
    <h3>Quero criar uma conta</h3>
    <ol>
      <li>Carrega em <b>Criar conta</b>, no canto superior direito.</li>
      <li>Escreve o nome que os outros vão ver, o email e uma password com pelo menos 8 caracteres.</li>
      <li>Também podes usar <b>Continuar com Google</b> e entrar com a tua conta Gmail, sem criar password.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>Esqueci-me da password</h3>
    <ol>
      <li>No ecrã de entrada, carrega em <b>Esqueci-me da password</b>.</li>
      <li>Escreve o email da conta. Vais receber um link válido durante 60 minutos.</li>
      <li>Abre o link e escolhe uma nova password.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>Quero mudar o idioma</h3>
    <p>No fundo de qualquer página escolhe <b>PT</b> ou <b>EN</b>. Com sessão iniciada, a escolha fica guardada na tua conta.</p>
  </article>

  <article class="usecase">
    <h3>Onde está cada coisa</h3>
    <ul>
      <li><b>Painel</b>: resumo, recordes e gráficos.</li>
      <li><b>Treinos</b>: lista com filtros, importação e exportação.</li>
      <li><b>Rede</b>: pessoas, pedidos e treinos partilhados. O número ao lado mostra os pedidos por responder.</li>
      <li><b>Atletas</b>: só aparece a treinadores.</li>
      <li><b>A tua fotografia ou iniciais</b>: abre um pequeno menu com <b>Ver perfil</b>, <b>Manual de utilização</b> e <b>Sair</b>.</li>
      <li><b>Registar treino</b>: o botão vermelho. No telemóvel fica fixo no canto inferior direito.</li>
    </ul>
  </article>
</section>

<section id="registar" class="help__section">
  <h2>Registar um treino</h2>

  <article class="usecase">
    <h3>Fiz 10 × 500 m com 70 s de recuperação</h3>
    <ol>
      <li>Carrega em <b>Registar treino</b>.</li>
      <li>Confirma a <b>data</b> e a <b>hora</b>. Se quiseres, escreve um título, por exemplo "Séries de 500 no Jamor", e o local.</li>
      <li>Na <b>Série 1</b> escreve <b>10</b> em Repetições e <b>500</b> em Distância (m). Aparecem 10 caixas de tempo.</li>
      <li>Em <b>Recuperação</b> escreve <kbd>1:10</kbd> ou <kbd>70</kbd>. Em <b>Tipo</b> escolhe se ficaste parado, a andar ou a trotar.</li>
      <li>Escreve o tempo de cada repetição, por exemplo <kbd>1:32.4</kbd> ou <kbd>92.4</kbd>. A média, o ritmo e o desvio vão sendo calculados enquanto escreves.</li>
      <li>Carrega em <b>Guardar treino</b>.</li>
    </ol>
    <p class="tip">Para saltar para a caixa seguinte usa a tecla Tab ou o "seguinte" do teclado do telemóvel.</p>
  </article>

  <article class="usecase">
    <h3>O treino tinha várias séries (ex.: 4 × 400 m + 4 × 200 m)</h3>
    <ol>
      <li>Preenche a primeira série com 4 × 400.</li>
      <li>Carrega em <b>Adicionar série</b> e preenche 4 × 200.</li>
      <li>Cada série tem a sua recuperação, o seu tempo alvo e as suas estatísticas. Para tirar uma série, usa <b>Remover série</b>.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>Tinha um tempo alvo para cada repetição</h3>
    <p>Escreve-o em <b>Tempo alvo</b> na série, por exemplo <kbd>1:32</kbd>. Na página do treino passas a ver a diferença de cada repetição para o alvo.</p>
  </article>

  <article class="usecase">
    <h3>Quero guardar as condições e as sensações</h3>
    <p>Por baixo das séries há campos opcionais: <b>Tipo de piso</b> (pista ao ar livre, coberta, estrada…), <b>Condições</b> (vento, chuva), <b>Temperatura</b>, <b>Esforço percebido</b> de 1 a 10 e <b>Notas</b> para o aquecimento, as sapatilhas ou o que quiseres.</p>
  </article>

  <article class="usecase">
    <h3>Repito o mesmo treino todas as semanas</h3>
    <ol>
      <li>Abre o treino da semana passada.</li>
      <li>Carrega em <b>Duplicar</b>. Abre um treino novo com as mesmas séries e a data de hoje.</li>
      <li>Escreve os tempos novos e guarda.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>Enganei-me num tempo</h3>
    <p>Abre o treino, carrega em <b>Editar</b>, corrige e guarda.</p>
  </article>
</section>

<section id="gpx" class="help__section">
  <h2>Usar o ficheiro GPX do relógio</h2>

  <article class="usecase">
    <h3>Quero preencher os tempos a partir do relógio</h3>
    <ol>
      <li>Exporta a atividade em GPX da aplicação do teu relógio (Garmin Connect, Coros, Strava…).</li>
      <li>Em <b>Registar treino</b>, carrega em <b>Preencher a partir de GPX</b> e escolhe o ficheiro.</li>
      <li>A aplicação deteta as repetições pela velocidade e arredonda à distância de pista (294 m passa a 300 m). Ignora o aquecimento e o retorno à calma.</li>
      <li>A data, a hora, o local e, se o relógio os tiver gravado, a frequência cardíaca média e a temperatura são preenchidos.</li>
      <li><b>Confirma os tempos</b> antes de guardar: o GPS na pista tem erros de alguns metros.</li>
    </ol>
    <p class="tip">O ficheiro GPX não fica guardado no servidor. Só os tempos que confirmares são guardados.</p>
  </article>

  <article class="usecase">
    <h3>O local veio com o nome errado</h3>
    <p>Muda o nome no campo <b>Local</b>. Da próxima vez que treinares no mesmo sítio, a aplicação usa o nome que escolheste.</p>
  </article>
</section>

<section id="analisar" class="help__section">
  <h2>Analisar um treino</h2>

  <article class="usecase">
    <h3>Quero saber se fui regular</h3>
    <p>Abre o treino na lista <b>Treinos</b>. Cada série mostra:</p>
    <ul>
      <li><b>Média</b>, <b>Melhor</b>, <b>Pior</b> e <b>Ritmo</b> por km.</li>
      <li><b>Desvio</b>: quanto os tempos variaram. Quanto mais baixo, mais regular.</li>
      <li>Um gráfico com a diferença de cada repetição para a média: azul se foi mais rápida, vermelho se foi mais lenta.</li>
      <li>Com o botão <b>Velocidade</b> vês a velocidade de cada repetição em vez da diferença.</li>
      <li>A marca dourada <b>Recorde pessoal</b> aparece na repetição que bateu o teu melhor tempo nessa distância.</li>
    </ul>
  </article>
</section>

<section id="procurar" class="help__section">
  <h2>Encontrar e organizar treinos</h2>

  <article class="usecase">
    <h3>Quero ver todos os 1000 m deste ano</h3>
    <ol>
      <li>Abre <b>Treinos</b>.</li>
      <li>Escolhe <b>Distância</b> 1000 e o <b>Ano</b>. Também podes filtrar por <b>Mês</b>, <b>Local</b>, texto livre e <b>Estado</b>.</li>
      <li>Carrega em <b>Filtrar</b>. <b>Limpar filtros</b> volta a mostrar tudo.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>Um treino correu mal e não quero que conte nos recordes</h3>
    <p>Abre o treino e carrega em <b>Desativar</b>. O treino fica guardado, mas não conta no painel nem nos melhores tempos. Para o voltar a contar, carrega em <b>Reativar</b>. Na lista, o filtro <b>Estado → Inativos</b> mostra só estes.</p>
  </article>

  <article class="usecase">
    <h3>Quero mudar muitos treinos de uma vez</h3>
    <ol>
      <li>Na lista <b>Treinos</b>, marca a caixa de cada treino, ou a caixa do topo para selecionar todos os desta página.</li>
      <li>Na barra que aparece escolhe: desativar, reativar, apagar ou mudar <b>Quem pode ver</b>.</li>
    </ol>
    <p class="tip">Apagar não se pode desfazer. Se tiveres dúvidas, desativa.</p>
  </article>
</section>

<section id="painel" class="help__section">
  <h2>Painel e recordes</h2>

  <article class="usecase">
    <h3>Quero ver a minha evolução</h3>
    <p>O <b>Painel</b> é a primeira página depois de entrares. Mostra:</p>
    <ul>
      <li><b>Melhores tempos</b> em cada distância.</li>
      <li>Totais de treinos, km em repetições e repetições, e o que fizeste nos últimos 30 dias.</li>
      <li>Quantas <b>semanas seguidas</b> treinaste.</li>
      <li><b>Volume por semana</b> nas últimas 12 semanas.</li>
      <li><b>Evolução</b>: o tempo médio e a melhor repetição de cada treino numa distância. Muda a distância no seletor do gráfico. São precisos pelo menos 2 treinos dessa distância.</li>
      <li><b>Distâncias mais feitas</b> e <b>Últimos treinos</b>.</li>
    </ul>
  </article>
</section>

<section id="excel" class="help__section">
  <h2>Importar e exportar</h2>

  <article class="usecase">
    <h3>Tenho anos de treinos numa folha Excel</h3>
    <ol>
      <li>Em <b>Treinos</b>, carrega em <b>Importar do Excel</b>.</li>
      <li>Escolhe o ficheiro .xlsx (até 5 MB). Todos os separadores são lidos.</li>
      <li>Vês a lista de treinos encontrados. Os dias em que já tens treino aparecem assinalados.</li>
      <li>Escolhe os que queres e carrega em <b>Importar selecionados</b>.</li>
    </ol>
    <p class="tip">A página de importação explica como deve estar a folha: a distância no nome do separador (ex.: "400m") e um cabeçalho "Data | 1 | 2 | 3 … | Intervalo".</p>
  </article>

  <article class="usecase">
    <h3>Quero os meus dados numa folha de cálculo</h3>
    <p>Em <b>Treinos</b> ou em <b>Ver perfil</b>, carrega em <b>Exportar treinos (CSV)</b>. O ficheiro abre no Excel, no Numbers ou no Google Sheets.</p>
  </article>
</section>

<section id="partilhar" class="help__section">
  <h2>Partilhar treinos</h2>

  <article class="usecase">
    <h3>Quero que outros vejam um treino</h3>
    <p>Cada treino tem um campo <b>Quem pode ver</b>, no formulário:</p>
    <ul>
      <li><b>Só eu</b>: privado.</li>
      <li><b>Conexões</b>: as pessoas com quem estás ligado na Rede.</li>
      <li><b>Público</b>: qualquer utilizador da aplicação, no teu perfil.</li>
    </ul>
    <p>Um treino novo usa a mesma opção do último que registaste. Para mudar vários de uma vez, usa a seleção na lista <b>Treinos</b>.</p>
    <p class="tip">Os teus treinadores veem sempre todos os teus treinos, incluindo os que estão em "Só eu".</p>
  </article>

  <article class="usecase">
    <h3>Quero publicar um treino no Instagram ou noutra rede social</h3>
    <ol>
      <li>Abre o treino e carrega em <b>Partilhar</b>.</li>
      <li>Escolhe o formato: <b>Publicação</b> (4:5, para o feed) ou <b>História</b> (9:16).</li>
      <li>Escolhe o que mostrar: o <b>Gráfico</b> da velocidade de cada repetição, os <b>Tempos</b> de todas as repetições ou <b>Nada</b>. Marca ou desmarca também o avatar e nome, a data e local, os números (média, mais rápida, volume e repetições) e os recordes pessoais.</li>
      <li>Escolhe as cores: <b>Noite</b>, <b>Tartan</b>, <b>Claro</b>, <b>Relva</b> ou <b>Azul</b>. Marca <b>Fundo transparente</b> para pores a imagem por cima de uma foto tua (as cores escolhidas passam a ser as do texto).</li>
      <li>No telemóvel, carrega em <b>Partilhar imagem</b> e escolhe a aplicação. No computador, carrega em <b>Copiar imagem</b> e cola-a onde quiseres (Ctrl+V), ou em <b>Descarregar PNG</b>.</li>
    </ol>
    <p>A imagem leva o teu avatar, a média, a repetição mais rápida, o volume, os recordes pessoais do dia e o gráfico ou os tempos, com o endereço www.series.corridas.eu no rodapé.</p>
    <p class="tip">Só o autor pode criar a imagem. Os treinadores não veem o botão nos treinos dos atletas.</p>
  </article>
</section>

<section id="rede" class="help__section">
  <h2>Rede de atletas</h2>

  <article class="usecase">
    <h3>Quero ligar-me a um colega de treino</h3>
    <ol>
      <li>Abre <b>Rede</b> e escreve o nome ou o clube em <b>Procurar pessoas</b>.</li>
      <li>Carrega em <b>Pedir conexão</b>, na lista ou no perfil da pessoa.</li>
      <li>Quando ela aceitar, passam a ver os treinos um do outro marcados como "Conexões".</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>Alguém me pediu conexão</h3>
    <p>Aparece um número ao lado de <b>Rede</b>. Abre a página e, em <b>Pedidos recebidos</b>, carrega em <b>Aceitar</b> ou <b>Rejeitar</b>. Quem é rejeitado não recebe aviso.</p>
  </article>

  <article class="usecase">
    <h3>Quero ver o que os outros andam a treinar</h3>
    <p>Em <b>Rede → Atividade recente</b> vês os treinos partilhados das tuas conexões. Carrega no nome de alguém para veres o perfil e os treinos que partilha contigo.</p>
  </article>

  <article class="usecase">
    <h3>Já não quero estar ligado a alguém</h3>
    <p>Na lista de <b>Conexões</b>, ou no perfil da pessoa, carrega em <b>Remover</b>. Deixam de ver os treinos "Conexões" um do outro.</p>
  </article>
</section>

<section id="treinador" class="help__section">
  <h2>Sou treinador</h2>
  <p>Um treinador vê <b>todos</b> os treinos dos seus atletas, incluindo os privados, e o painel de cada um. Cada atleta tem de aceitar a ligação.</p>

  <article class="usecase">
    <h3>Quero ativar o perfil de treinador</h3>
    <ol>
      <li>Carrega na tua fotografia, no canto superior direito, e em <b>Ver perfil</b>.</li>
      <li>Carrega em <b>Editar perfil</b>, marca <b>Sou treinador</b> e carrega em <b>Guardar perfil</b>.</li>
      <li>Aparece o menu <b>Atletas</b>, e a etiqueta "Treinador" junto ao teu nome. No telemóvel, os Atletas abrem-se a partir da página <b>Rede</b>.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>Quero adicionar um atleta</h3>
    <ol>
      <li>Procura o atleta em <b>Rede</b> e abre o perfil dele.</li>
      <li>Carrega em <b>Convidar como atleta</b>.</li>
      <li>Quando o atleta aceitar, ele aparece na página <b>Atletas</b>.</li>
    </ol>
    <p class="tip">O atleta também te pode pedir a ti. Nesse caso o pedido aparece em <b>Rede → Pedidos recebidos</b> e carregas em <b>Aceitar como atleta</b>.</p>
  </article>

  <article class="usecase">
    <h3>Quero ver como estão os meus atletas</h3>
    <ol>
      <li>Abre <b>Atletas</b>. A tabela mostra o último treino, os treinos e os km dos últimos 30 dias de cada atleta.</li>
      <li>Carrega no nome de um atleta para abrir o <b>painel</b> dele, ou em <b>Treinos</b> para veres a lista completa com filtros.</li>
      <li>No topo há uma barra "Vista de treinador" para mudares de atleta ou voltares aos teus treinos.</li>
    </ol>
    <p class="tip">Vês e analisas os treinos dos atletas, mas não os podes editar nem apagar.</p>
  </article>

  <article class="usecase">
    <h3>Deixei de treinar um atleta</h3>
    <p>Em <b>Atletas</b>, carrega em <b>Deixar de treinar</b>. Perdes logo o acesso aos treinos dele.</p>
  </article>
</section>

<section id="atleta" class="help__section">
  <h2>Tenho treinador</h2>

  <article class="usecase">
    <h3>Quero que o meu treinador veja os meus treinos</h3>
    <ol>
      <li>O teu treinador tem de ter conta e ter ativado "Sou treinador".</li>
      <li>Procura-o em <b>Rede</b>, abre o perfil e carrega em <b>Pedir para ser meu treinador</b>.</li>
      <li>Quando ele aceitar, passa a ver todos os teus treinos.</li>
    </ol>
  </article>

  <article class="usecase">
    <h3>Um treinador convidou-me</h3>
    <p>Em <b>Rede → Pedidos recebidos</b>, carrega em <b>Aceitar como treinador</b>, ou em <b>Rejeitar</b>. Só aceites quem conheces: o treinador vê também os treinos que marcaste como "Só eu".</p>
  </article>

  <article class="usecase">
    <h3>Mudei de treinador</h3>
    <p>Em <b>Rede → Os meus treinadores</b>, ou no perfil do treinador antigo, carrega em <b>Terminar</b>. Ele deixa de ver os teus treinos privados. Depois pede ao novo treinador como explicado acima. Podes ter mais do que um treinador.</p>
  </article>
</section>

<section id="perfil" class="help__section">
  <h2>Perfil e conta</h2>

  <article class="usecase">
    <h3>Quero atualizar os meus dados</h3>
    <p>Carrega na tua fotografia, em <b>Ver perfil</b> e depois em <b>Editar perfil</b>. Aí podes mudar o nome a apresentar, o avatar (JPG, PNG ou WebP até 2 MB), a data de nascimento, o sexo, o peso, a altura, o clube, o país, a prova principal e o idioma. Carrega em <b>Guardar perfil</b> no fim.</p>
  </article>

  <article class="usecase">
    <h3>Quero mudar o email ou a password</h3>
    <p>Em <b>Ver perfil → Editar perfil</b> há uma secção para cada. Para mudar a password tens de escrever a password atual.</p>
  </article>

  <article class="usecase">
    <h3>Quero sair ou apagar a conta</h3>
    <ul>
      <li><b>Sair</b>: carrega na tua fotografia, no canto superior direito, e escolhe <b>Sair</b>.</li>
      <li><b>Apagar conta</b> está no fundo de <b>Editar perfil</b>. Apaga a conta e todos os treinos para sempre. Exporta primeiro o CSV se quiseres guardar os dados.</li>
    </ul>
  </article>
</section>

<section id="faq" class="help__section">
  <h2>Perguntas frequentes</h2>
  <dl class="faq">
    <dt>Como escrevo os tempos?</dt>
    <dd><kbd>1:32.4</kbd> (minutos:segundos.décimas) ou <kbd>92.4</kbd> (só segundos). No teclado numérico do telemóvel também podes escrever <kbd>1.32.4</kbd>. Para provas longas funciona <kbd>1:02:15</kbd>.</dd>
    <dt>Quem vê os meus treinos?</dt>
    <dd>Tu, os teus treinadores e quem a opção "Quem pode ver" de cada treino permitir. O primeiro treino é "Só eu". Os seguintes usam a opção do último treino que registaste.</dd>
    <dt>Qual é a diferença entre desativar e apagar um treino?</dt>
    <dd>Desativar esconde o treino das estatísticas e pode ser desfeito. Apagar remove-o para sempre.</dd>
    <dt>O ficheiro GPX fica guardado?</dt>
    <dd>Não. Só são guardados os tempos e os dados que confirmares.</dd>
    <dt>Posso usar no telemóvel?</dt>
    <dd>Sim. A aplicação adapta-se ao ecrã. No browser do telemóvel, usa "Adicionar ao ecrã principal" para a abrires como uma app.</dd>
    <dt>A minha conta não entra e diz que está desativada.</dt>
    <dd>Um administrador desativou a conta. Os teus treinos estão guardados. Contacta o administrador para a reativar.</dd>
  </dl>
</section>

<?php };
