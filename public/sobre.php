<?php

require_once __DIR__ . '/partials/layout.php';

topo('Sobre', 'sobre');
?>
<div class="coluna larga">
  <h1 class="centro destaque">Do caos das abas abertas a uma estante sem fronteiras</h1>
  <div class="sobre-intro">
    <img class="sobre-img" src="/assets/img/sentada.png" width="359" height="400" alt="Ilustração de uma pessoa sentada de pernas cruzadas, lendo um livro rosa.">
    <div class="texto">
      <p>O Entre Abas nasceu da rotina real de leitores apaixonados que vivem entre diferentes universos literários. Sabemos bem como é acumular dezenas de abas abertas no navegador do celular ou do computador para não perder o fio da meada daquela história incrível no AO3, Wattpad, Spirit ou Tumblr, enquanto tentamos acompanhar nossas leituras tradicionais em livros.</p>
      <p>Hoje, nosso propósito vai muito além de guardar links: queremos devolver a tranquilidade e o prazer de ler no ambiente digital, oferecendo um catálogo único, organizado e feito sob medida para quem respira histórias todos os dias.</p>
    </div>
  </div>

  <div data-abas="sobre">
    <div class="abas abas-3" role="tablist" aria-label="Missão, Visão e Valores">
      <button type="button" role="tab" id="aba-missao" aria-controls="p-missao" aria-selected="true">Missão</button>
      <button type="button" role="tab" id="aba-visao" aria-controls="p-visao" aria-selected="false" tabindex="-1">Visão</button>
      <button type="button" role="tab" id="aba-valores" aria-controls="p-valores" aria-selected="false" tabindex="-1">Valores</button>
    </div>
    <div class="painel-sobre" role="tabpanel" id="p-missao" aria-labelledby="aba-missao">
      <p>Simplificar e centralizar a vida literária digital, oferecendo aos leitores de fanfics e livros uma estante única, intuitiva e livre de distrações para catalogar, acompanhar e redescobrir suas histórias favoritas, independentemente da plataforma de origem.</p>
    </div>
    <div class="painel-sobre" role="tabpanel" id="p-visao" aria-labelledby="aba-visao" hidden>
      <p>Ser a principal plataforma de organização literária para leitores digitais e comunidades de fãs no Brasil, reconhecida por unir tecnologia, praticidade e paixão pela leitura em um ecossistema sem fronteiras.</p>
    </div>
    <div class="painel-sobre" role="tabpanel" id="p-valores" aria-labelledby="aba-valores" hidden>
      <ul>
        <li><strong>Paixão por Histórias:</strong> Valorizamos a literatura em todas as suas formas, do autor independente de fanfics aos grandes clássicos mundiais.</li>
        <li><strong>Simplicidade e Foco:</strong> Combatemos a sobrecarga digital com um design limpo, rápido e centrado na experiência do leitor.</li>
        <li><strong>Liberdade de Escolha:</strong> Respeitamos a web aberta, dando ao usuário autonomia total para organizar obras de qualquer fonte em um só lugar.</li>
        <li><strong>Inovação Acessível:</strong> Utilizamos integração de dados e tecnologia web para transformar o hábito de gerenciar leituras em algo leve e prazeroso.</li>
      </ul>
    </div>
  </div>
</div>
<?php rodape(); ?>
