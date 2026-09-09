<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contato · Disco Burguer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Righteous&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/divulgacao/contato.css">
</head>

<body>
    <header>
        <?php
        include __DIR__ . "/../../header.php";
        ?>
    </header>

    <main>
        <section class="contato">

            <!-- sol + grade decorativos -->
            <div class="cena-fundo" aria-hidden="true">
                <div class="sol"></div>
                <div class="grade"></div>
            </div>

            <div class="container-contato">

                <h1 class="titulo-contato">Contato</h1>

                <div class="grid-contato">

                    <!-- FORMULÁRIO -->
                    <div class="painel painel-form">
                        <div class="painel-cabecalho">
                            <span class="icone-cabecalho">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 11.5L21 3L13.5 21L10.5 13.5L3 11.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            </span>
                            <div>
                                <h2>Envie uma mensagem</h2>
                                <p>Preencha os campos abaixo e entraremos em contato o mais breve possível.</p>
                            </div>
                        </div>

                        <form action="" method="POST" class="form-contato">

                            <div class="linha-form">
                                <div class="campo">
                                    <span class="campo-icone">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M4 20c0-3.5 3.5-6 8-6s8 2.5 8 6" stroke="currentColor" stroke-width="1.8"/></svg>
                                    </span>
                                    <input type="text" name="nome" placeholder="Seu nome*" required>
                                </div>
                                <div class="campo">
                                    <span class="campo-icone">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 6l9 7 9-7" stroke="currentColor" stroke-width="1.8"/></svg>
                                    </span>
                                    <input type="email" name="email" placeholder="Seu e-mail*" required>
                                </div>
                            </div>

                            <div class="campo campo-completo">
                                <span class="campo-icone">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 5c0 8 7 15 15 15l2-4-6-2-2 2c-2-1-4-3-5-5l2-2-2-6-4 2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                </span>
                                <input type="tel" name="telefone" placeholder="Seu telefone (opcional)">
                            </div>

                            <div class="campo campo-completo">
                                <span class="campo-icone">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M20 12a8 8 0 1 1-3.2-6.4" stroke="currentColor" stroke-width="1.8"/><path d="M20 4l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                </span>
                                <select name="assunto" required>
                                    <option value="" selected disabled>Selecione um assunto</option>
                                    <option value="pedido">Dúvida sobre pedido</option>
                                    <option value="reserva">Reserva de mesa</option>
                                    <option value="elogio">Elogio</option>
                                    <option value="reclamacao">Reclamação</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>

                            <div class="campo campo-completo campo-mensagem">
                                <span class="campo-icone campo-icone-topo">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 4h16v12H8l-4 4V4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                </span>
                                <textarea name="comentario" maxlength="500" placeholder="Sua mensagem..." required></textarea>
                                <span class="contador">0 / 500</span>
                            </div>

                            <button type="submit" class="botao-enviar">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 11.5L21 3L13.5 21L10.5 13.5L3 11.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                Enviar mensagem
                            </button>
                        </form>
                    </div>

                    <!-- INFORMAÇÕES -->
                    <div class="lista-info">

                        <a href="https://wa.me/5511945678901" class="painel painel-info painel-link">
                            <span class="info-icone icone-whats">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2Zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20Zm4.4-5.9c-.2-.1-1.4-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.6.8-.8 1-.1.2-.3.2-.5.1-.2-.1-1-.4-2-1.2-.7-.6-1.2-1.4-1.4-1.6-.1-.2 0-.4.1-.5.1-.1.2-.3.4-.4.1-.1.2-.3.2-.4.1-.2 0-.3 0-.5-.1-.1-.6-1.4-.8-1.9-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.2-.9.9-.9 2.1s.9 2.5 1.1 2.6c.1.2 1.8 2.8 4.4 3.8.6.3 1.1.4 1.5.5.6.2 1.2.2 1.6.1.5-.1 1.4-.6 1.6-1.1.2-.5.2-1 .1-1.1 0-.1-.2-.2-.4-.3Z"/></svg>
                            </span>
                            <span class="info-texto">
                                <strong>WhatsApp</strong>
                                <span class="info-destaque">(11) 94567-8901</span>
                                <span class="info-legenda">Chame no Whats! Resposta rápida</span>
                            </span>
                            <span class="info-seta">›</span>
                        </a>

                        <a href="mailto:contato@discoburguer.com.br" class="painel painel-info painel-link">
                            <span class="info-icone icone-email">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 6l9 7 9-7" stroke="currentColor" stroke-width="1.8"/></svg>
                            </span>
                            <span class="info-texto">
                                <strong>E-mail</strong>
                                <span class="info-destaque">contato@discoburguer.com.br</span>
                                <span class="info-legenda">Respondemos em até 24h</span>
                            </span>
                            <span class="info-seta">›</span>
                        </a>

                        <div class="painel painel-info">
                            <span class="info-icone icone-relogio">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </span>
                            <span class="info-texto">
                                <strong>Nosso horário</strong>
                                <span class="linha-horario"><span>Segunda a Quinta</span><span>11h às 23h</span></span>
                                <span class="linha-horario"><span>Sexta e Sábado</span><span>11h às 00h</span></span>
                                <span class="linha-horario"><span>Domingo</span><span>11h às 23h</span></span>
                            </span>
                        </div>

                        <div class="painel painel-info painel-endereco">
                            <span class="info-icone icone-local">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.5 7-11a7 7 0 1 0-14 0c0 4.5 7 11 7 11Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.8"/></svg>
                            </span>
                            <span class="info-texto">
                                <strong>Nosso endereço</strong>
                                <span class="info-legenda">Rua Fagundes, 80 — Liberdade</span>
                                <span class="info-legenda">São Paulo - SP, 01503-000</span>
                            </span>
                            <a class="botao-mapa" href="https://maps.google.com/?q=Rua+Fagundes+80+Liberdade+São+Paulo" target="_blank" rel="noopener">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6.5 7-11a7 7 0 1 0-14 0c0 4.5 7 11 7 11Z" stroke="currentColor" stroke-width="1.8"/></svg>
                                Ver no Google Maps
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <?php
        include __DIR__ . "/../../footer.php";
        ?>
    </footer>

    <script>
        const textarea = document.querySelector('.campo-mensagem textarea');
        const contador = document.querySelector('.contador');
        if (textarea && contador) {
            textarea.addEventListener('input', () => {
                contador.textContent = `${textarea.value.length} / 500`;
            });
        }
    </script>
</body>

</html>