<?php $en = current_lang() === 'en'; ?>
<header class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="muted"><?= $en ? 'Last updated: 29 October 2026' : 'Última atualização: 29 de outubro de 2026' ?></p>
  </div>
</header>

<?php if (!$en): ?>
<section class="panel legal">
  <h2>1. Quem é responsável</h2>
  <p>O Séries é operado por <b>[NOME DO RESPONSÁVEL]</b>. Para qualquer questão sobre os teus dados, escreve para <b>[EMAIL DE CONTACTO]</b>.</p>

  <h2>2. Que dados guardamos</h2>
  <ul>
    <li><b>Conta:</b> email, nome a apresentar e password (guardada apenas como hash, nunca em texto). Se entrares com o Google, guardamos o email e o identificador da tua conta Google.</li>
    <li><b>Perfil:</b> dados que escolheres preencher, como peso, altura, biografia e avatar, e as tuas escolhas de privacidade.</li>
    <li><b>Treinos:</b> datas, horas, séries, distâncias, tempos, frequência cardíaca, notas, condições e o local (nome e coordenadas) de cada treino.</li>
    <li><b>Ficheiros GPX:</b> quando carregas um GPX, a aplicação lê-o para preencher o treino e apaga-o logo a seguir. O ficheiro não fica guardado.</li>
    <li><b>Strava:</b> se ligares a conta Strava, guardamos os tokens de acesso e os treinos que importares. Não copiamos o teu histórico do Strava.</li>
    <li><b>Ligações sociais:</b> pedidos de conexão entre atletas e os treinadores que escolheres.</li>
  </ul>

  <h2>3. Para que usamos os dados</h2>
  <p>Para manter a tua conta, mostrar os teus treinos e estatísticas, deixar-te partilhar com quem escolheres e enviar emails da conta (recuperação de password, pedidos de conexão e os avisos que ativares no perfil). Não usamos os teus dados para publicidade nem para perfis de marketing.</p>

  <h2>4. Dados do Strava</h2>
  <p>Só lemos as atividades de corrida da tua conta Strava quando pedes para importar uma. Não partilhamos, não vendemos nem publicamos esses dados e não os usamos para publicidade. Podes desligar a conta a qualquer momento, na página <b>Importar do Strava</b> ou nas definições do Strava; ao desligar, revogamos o acesso e apagamos os tokens guardados. Cumprimos as <a href="https://www.strava.com/legal/api" target="_blank" rel="noopener">condições da API do Strava</a>.</p>

  <h2>5. Serviços de terceiros</h2>
  <ul>
    <li><b>Google:</b> para o login com Google, e para carregar as fontes de letra (Google Fonts), que o teu browser pede aos servidores da Google.</li>
    <li><b>OpenStreetMap (Overpass e Nominatim):</b> para dar nome ao local de um treino, enviamos as coordenadas desse local a estes serviços.</li>
    <li><b>Strava:</b> para a ligação e a importação de treinos, quando a usas.</li>
    <li><b>Serviço de email do alojamento:</b> para enviar as mensagens da conta.</li>
  </ul>

  <h2>6. Com quem partilhamos</h2>
  <p>Não vendemos os teus dados. Os teus treinos só são vistos por quem tu escolheres: a visibilidade de cada treino e do perfil é tua, e os treinadores só veem os atletas que aceitaram ligar-se a eles.</p>

  <h2>7. Cookies</h2>
  <p>Usamos apenas o cookie de sessão necessário para manteres o login (<code>series_sid</code>). Não usamos cookies de publicidade nem de medição de audiências.</p>

  <h2>8. Quanto tempo guardamos</h2>
  <p>Enquanto a conta existir. Podes apagar a conta na página de perfil, o que apaga também os teus treinos. Antes disso, podes exportar os teus treinos em CSV, na página de treinos.</p>

  <h2>9. Os teus direitos</h2>
  <p>Podes pedir o acesso, a correção, a portabilidade ou o apagamento dos teus dados, e opor-te ao tratamento. Para isso, usa as funções da aplicação ou escreve para o contacto acima. Se achares que não tratamos bem os teus dados, podes apresentar queixa à <a href="https://www.cnpd.pt" target="_blank" rel="noopener">Comissão Nacional de Proteção de Dados</a>.</p>

  <h2>10. Segurança</h2>
  <p>A aplicação usa HTTPS, guarda as passwords com hash e protege os formulários com tokens de segurança. Nenhum sistema é totalmente seguro: se algo correr mal, avisamos os utilizadores afetados.</p>

  <h2>11. Alterações</h2>
  <p>Se alterarmos esta política, publicamos a nova versão nesta página e indicamos a data de atualização.</p>
</section>
<?php else: ?>
<section class="panel legal">
  <h2>1. Who is responsible</h2>
  <p>Séries is operated by <b>[NAME OF THE CONTROLLER]</b>. For any question about your data, write to <b>[CONTACT EMAIL]</b>.</p>

  <h2>2. What data we store</h2>
  <ul>
    <li><b>Account:</b> email, display name and password (stored only as a hash, never in plain text). If you sign in with Google, we store your email and your Google account identifier.</li>
    <li><b>Profile:</b> the details you choose to fill in, such as weight, height, bio and avatar, and your privacy choices.</li>
    <li><b>Workouts:</b> dates, times, sets, distances, split times, heart rate, notes, conditions and the place (name and coordinates) of each workout.</li>
    <li><b>GPX files:</b> when you upload a GPX file, the app reads it to fill in the workout and deletes it straight away. The file is not kept.</li>
    <li><b>Strava:</b> if you connect your Strava account, we store the access tokens and the workouts you import. We do not copy your Strava history.</li>
    <li><b>Social links:</b> connection requests between athletes and the coaches you choose.</li>
  </ul>

  <h2>3. What we use the data for</h2>
  <p>To keep your account running, show your workouts and statistics, let you share with the people you choose, and send account emails (password recovery, connection requests and the notices you turn on in your profile). We do not use your data for advertising or marketing profiles.</p>

  <h2>4. Strava data</h2>
  <p>We only read the running activities in your Strava account when you ask to import one. We do not share, sell or publish that data, and we do not use it for advertising. You can disconnect at any time from the <b>Import from Strava</b> page or from your Strava settings. When you disconnect, we revoke access and delete the stored tokens. We follow the <a href="https://www.strava.com/legal/api" target="_blank" rel="noopener">Strava API terms</a>.</p>

  <h2>5. Third-party services</h2>
  <ul>
    <li><b>Google:</b> for sign-in with Google, and for loading the Google Fonts typefaces, which your browser requests from Google's servers.</li>
    <li><b>OpenStreetMap (Overpass and Nominatim):</b> to name the place of a workout, we send the coordinates of that place to these services.</li>
    <li><b>Strava:</b> for the connection and workout import, when you use it.</li>
    <li><b>The hosting provider's email service:</b> to send account messages.</li>
  </ul>

  <h2>6. Who we share with</h2>
  <p>We do not sell your data. Your workouts are only seen by the people you choose: the visibility of each workout and of your profile is up to you, and coaches only see the athletes who accepted a connection with them.</p>

  <h2>7. Cookies</h2>
  <p>We only use the session cookie needed to keep you signed in (<code>series_sid</code>). We do not use advertising or audience-measurement cookies.</p>

  <h2>8. How long we keep data</h2>
  <p>As long as your account exists. You can delete your account on the profile page, which also deletes your workouts. Before that, you can export your workouts as CSV from the workouts page.</p>

  <h2>9. Your rights</h2>
  <p>You can ask for access, correction, portability or deletion of your data, and object to its processing. Use the app's features or write to the contact above. If you think we are not handling your data properly, you can complain to the <a href="https://www.cnpd.pt" target="_blank" rel="noopener">Portuguese data protection authority (CNPD)</a>.</p>

  <h2>10. Security</h2>
  <p>The app uses HTTPS, stores passwords as hashes and protects forms with security tokens. No system is completely secure: if something goes wrong, we will warn the affected users.</p>

  <h2>11. Changes</h2>
  <p>If we change this policy, we will publish the new version on this page and show the update date.</p>
</section>
<?php endif; ?>
