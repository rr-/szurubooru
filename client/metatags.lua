ngx.req.read_body()

local page_html = ngx.location.capture("/index.htm")

ngx.req.set_header("Accept", "application/json")
local server_info = cjson.decode((ngx.location.capture("/_internal_api/info")).body)

-- local document = gumbo.parse(page_html.body)
--
-- function add_meta_tag (property, content)
--   local new_element = document:createElement("meta")
--   document.head:appendChild(new_element)
--   new_element:setAttribute("property", property)
--   new_element:setAttribute("content", content)
-- end

local additional_tags = ""

local function add_meta_tag (property, content)
  additional_tags = additional_tags .. "<meta property=\"" .. property .. "\" content=\"" .. content:gsub('"', '\\"') .. "\"/>"
end

-- Add the site name tag
add_meta_tag("og:site_name", server_info.config.name)
add_meta_tag("og:url", ngx.var.external_host_url .. ngx.var.request_uri)

if ngx.var.request_uri_path:match('^/post') then
  local post_info = cjson.decode((ngx.location.capture("/_internal_api"..ngx.var.request_uri_path)).body)
  -- If no permission to access, fields will be nil, thus cannot be concat'd
  if post_info.contentUrl then
    add_meta_tag("og:image", ngx.var.external_host_url .. '/' .. post_info.contentUrl)
  end
  if post_info.id then
    add_meta_tag("og:title", server_info.config.name .. " - Post " .. post_info.id)
  end
end

local final_response = page_html.body:gsub("{{ generated_head_tags }}", additional_tags)

-- Set the content type back to HTML
ngx.header.content_type = 'text/html';

-- ngx.say(page_html.body)
ngx.say(final_response)
