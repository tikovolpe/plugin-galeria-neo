# Galeria Neo

Plugin WordPress para exibir galerias de logos de associados e parceiros como widget Elementor, com suporte a grid responsivo, carrossel automático e efeitos hover.

---

## Requisitos

| Componente | Versão mínima |
|------------|--------------|
| WordPress | 6.0 |
| PHP | 8.0 |
| Elementor (Free) | qualquer versão recente |

> Não requer Elementor Pro.

---

## Instalação

1. Acesse a [página de releases](https://github.com/tikovolpe/plugin-galeria-neo/releases) e baixe o arquivo `.zip` da versão mais recente.
2. No painel do WordPress, vá em **Plugins → Adicionar novo → Enviar plugin**.
3. Selecione o arquivo `.zip` e clique em **Instalar agora**.
4. Ative o plugin.

---

## Cadastrando logos

O plugin cria um tipo de conteúdo próprio chamado **Logos** no menu do WordPress.

1. Acesse **Logos → Adicionar novo**.
2. Defina o **título** (nome do parceiro/associado).
3. Defina a **imagem destacada** — esta é a imagem exibida na galeria.
4. Opcionalmente, informe a **URL do parceiro** (campo "Link do logo") para tornar a imagem clicável.
5. Atribua uma **categoria de logo** se quiser filtrar por grupos (ex.: Associados, Parceiros).
6. Publique.

### Importação em massa

Vá em **Logos → Importar Logos** para criar posts automaticamente a partir de imagens já presentes na Biblioteca de Mídia cujo título contenha "associad", "parceir" ou "parceria".

---

## Usando o widget no Elementor

1. Abra uma página no editor Elementor.
2. Procure por **"Galeria Neo"** no painel de widgets.
3. Arraste o widget para a área de conteúdo.

### Configurações disponíveis

**Aba Conteúdo**

| Configuração | Descrição |
|---|---|
| Categoria | Filtra os logos por categoria. "Todas as categorias" exibe tudo. |
| Ordenar por | Título ou data de publicação. |
| Direção | Crescente ou decrescente. |
| Modo de exibição | Grid ou Carrossel (configurável por breakpoint). |
| Colunas | Número de colunas no grid (1–10, responsivo). |
| Espaçamento | Gap entre os itens (0–80px, responsivo). |
| Proporção da imagem | auto, 1:1, 4:3, 3:2, 16:9, 2:3 ou 3:4. |
| Ajuste da imagem | Cover, Contain ou Fill. |

**Aba Carrossel**

| Configuração | Descrição |
|---|---|
| Autoplay | Liga/desliga avanço automático. |
| Intervalo | Tempo entre slides (500–10000ms). |
| Transição | Duração da animação de troca (100–2000ms). |

**Aba Estilo**

- Padding, borda, sombra e border-radius das imagens.
- Alinhamento horizontal e vertical.
- Efeitos hover: nenhum, zoom, overlay colorido, escala de cinza ou opacidade.
- Cor, tamanho e margem dos dots do carrossel.

---

## Funcionalidades

### Grid responsivo
Exibe os logos em grade CSS com número de colunas e espaçamento configuráveis por breakpoint (mobile, tablet, desktop, widescreen).

### Carrossel
Modo carrossel com navegação por dots, suporte a swipe em touch, loop infinito e autoplay opcional. Transição suave configurável.

### Breakpoints adaptativos
O modo de exibição (grid ou carrossel) pode ser diferente para cada tamanho de tela — por exemplo, grid no desktop e carrossel no mobile.

### Efeitos hover
Cinco efeitos disponíveis para interação com o mouse: zoom, overlay colorido, escala de cinza, opacidade reduzida ou nenhum efeito.

### Links externos
Cada logo pode ter um link configurado que abre em nova aba com `rel="noopener noreferrer"`.

---

## Atualização automática

O plugin inclui um sistema de atualização integrado ao painel do WordPress via releases do GitHub.

- Quando uma nova versão é publicada no GitHub, o painel exibe a notificação de atualização normalmente (junto com outros plugins).
- Para verificar manualmente, acesse **Plugins**, localize o Galeria Neo e clique em **Verificar atualização**.
- O cache de verificação é renovado a cada 12 horas automaticamente.

---

## Licença

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)

Desenvolvido por [LVBA Comunicação](https://lvba.com.br).
