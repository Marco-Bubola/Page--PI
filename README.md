# PAGE - Sistema de Gerenciamento de Planos de Aula e Registro de Aulas

![PHP](https://img.shields.io/badge/PHP-8.2-blue?logo=php) ![MySQL](https://img.shields.io/badge/MySQL-9.2-blue?logo=mysql) ![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple?logo=bootstrap) ![Status](https://img.shields.io/badge/Projeto-Integrador-green)

> **Faculdade de Itapira — DSM Módulo 3**

---

## ✨ Visão Geral

O **PAGE** é um sistema web completo para gestão de planos de aula, disciplinas, turmas e registro de aulas, desenvolvido como Projeto Integrador do curso de DSM. O sistema é responsivo, moderno, com experiência fluida via AJAX e recursos visuais avançados.

---

## 👥 Equipe
- Cauã Araujo Vaz de Lima
- Inácio Custódio Silva
- João Gabriel Marreiro de Souza
- Marco Antônio Bubola
- Rafael Henrique Zanesco

---

## 🚀 Funcionalidades Principais

### Perfis de Usuário

- **Coordenador/Admin**
  - CRUD completo de disciplinas, turmas, planos, capítulos e tópicos
  - Vinculação de disciplinas a turmas
  - Gerenciamento de usuários (admin)
  - Visualização de estatísticas, últimos planos, cards-resumo
- **Professor**
  - Visualização de planos, capítulos e tópicos das turmas
  - Registro de aulas (marcando tópicos ministrados e tópicos extras)
  - Histórico detalhado de aulas

### Recursos Dinâmicos e Diferenciais

- Navegação por **wizard/stepper** entre capítulos/tópicos
- CRUD dinâmico via modais AJAX (sem recarregar a página)
- Paginação e filtros avançados em todas as telas
- Cards estilizados com badges de status, datas e ações rápidas
- Notificações visuais e feedback instantâneo
- Upload de foto de perfil para usuários
- Persistência de estado de navegação (abas, steps, filtros)
- Controle de permissões em todas as rotas

---

## 🛠️ Tecnologias Utilizadas

- **PHP 8.2** (backend)
- **MySQL 9.2** (banco de dados)
- **HTML5, CSS3, JavaScript** (frontend)
- **Bootstrap 5.3** (UI e responsividade)
- **Select2** (seleção múltipla)
- **XAMPP** (ambiente local)

---

## 🗂️ Estrutura do Projeto

```
Page--PI/
│
├── assets/
│   ├── img/         # Imagens e logos
│   ├── css/         # Estilos customizados
│   └── js/          # Scripts JS customizados
│
├── config/
│   └── conexao.php  # Conexão MySQL
│
├── controllers/     # Backend (CRUD, AJAX, autenticação)
│   ├── criar_*.php, editar_*.php, excluir_*.php
│   ├── *_ajax.php   # Todas as ações dinâmicas (capítulos, tópicos, planos, turmas, disciplinas)
│   ├── toggle_*.php # Ativar/cancelar status
│   ├── registrar_aula.php
│   ├── get_plano_id_by_*.php
│   └── ...
│
├── views/           # Telas e componentes visuais
│   ├── home_coordenador.php, home_professor.php
│   ├── disciplinas.php, turmas.php, planos.php
│   ├── registro_aulas.php, historico_aulas.php
│   ├── gerenciar_usuarios.php
│   ├── modais/ e modais_planos/ # Modais dinâmicos
│   └── ...
│
├── pi_page.sql      # Script do banco de dados
├── README.md        # Documentação
└── ...
```

---

## 💡 Fluxo Visual e Usabilidade

- **Cards e Badges**: Todas as entidades (turmas, disciplinas, planos, capítulos, tópicos) são exibidas em cards com badges de status (ativa, concluída, cancelada, em andamento, etc).
- **Modais Interativos**: Todas as ações CRUD são feitas em modais modernos, com validação e feedback visual.
- **Wizards/Steppers**: Navegação intuitiva entre capítulos e tópicos dos planos.
- **Filtros e Pesquisa**: Busca instantânea, filtros por status, ordenação e paginação AJAX.
- **Notificações**: Mensagens de sucesso, erro e alerta exibidas de forma elegante.
- **Upload de Imagem**: Usuários podem adicionar foto de perfil.
- **Acessibilidade**: Telas responsivas, navegação por teclado e contraste adequado.

---

## 📝 Como Rodar o Projeto

1. Clone este repositório ou baixe os arquivos.
2. Importe o banco de dados MySQL usando o arquivo `pi_page.sql`.
3. Configure o arquivo `/config/conexao.php` com os dados do seu banco.
4. Inicie o XAMPP e acesse: [http://localhost/Page--PI/](http://localhost/Page--PI/)

---

## 🔒 Segurança e Permissões

- Todas as rotas são protegidas por sessão e tipo de usuário.
- Senhas são criptografadas (password_hash).
- Uploads validados e restritos.

---

## 📚 Créditos

**PAGE** — Projeto Integrador DSM Módulo 3  
Faculdade de Itapira

---

> Projeto desenvolvido com foco em usabilidade, experiência do usuário e boas práticas de desenvolvimento web.